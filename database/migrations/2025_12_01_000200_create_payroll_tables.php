<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The Payroll engine: a real, generic gross-to-net computation
    // (basic + allowances + overtime − deductions) built on
    // tenant-editable salary components — deliberately NOT hardcoding
    // Saudi GOSI rates or income-tax brackets, since those are real
    // business/regulatory input this project has never been given (see
    // PROJECT_STATUS.md's standing note on this). A tenant can model
    // GOSI as a percentage-of-basic deduction component today; a
    // dedicated GOSI calculator is future work once those rules exist
    // as real input, not a guess.
    public function up(): void
    {
        Schema::create('salary_components', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('type', 15); // allowance|deduction
            $table->string('calculation_type', 20)->default('fixed'); // fixed|percentage_of_basic
            $table->decimal('default_amount', 12, 2)->default(0);
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'type']);
        });

        // The actual "Salary Structure": which components apply to which
        // employee and at what amount — a tenant-editable assignment
        // table, not a rigid template.
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('employee_id');
            $table->uuid('salary_component_id');
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('salary_component_id')->references('id')->on('salary_components')->cascadeOnDelete();
            $table->unique(['tenant_id', 'employee_id', 'salary_component_id']);
        });

        Schema::create('overtime_records', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('employee_id');
            $table->date('date');
            $table->decimal('hours', 5, 2);
            $table->decimal('rate_multiplier', 4, 2)->default(1.50);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status', 15)->default('pending'); // pending|approved|rejected
            $table->uuid('approved_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approved_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('run_number', 30);
            $table->unsignedSmallInteger('period_month');
            $table->unsignedSmallInteger('period_year');
            $table->string('status', 15)->default('draft'); // draft|processed|paid
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'run_number']);
            $table->unique(['tenant_id', 'period_month', 'period_year']);
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('payroll_run_id');
            $table->uuid('employee_id');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('total_allowances', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('gross_pay', 12, 2)->default(0);
            $table->decimal('net_pay', 12, 2)->default(0);
            $table->string('status', 15)->default('generated'); // generated|paid
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('payroll_run_id')->references('id')->on('payroll_runs')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->restrictOnDelete();
            $table->unique(['tenant_id', 'payroll_run_id', 'employee_id']);
        });

        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('payslip_id');
            $table->uuid('salary_component_id')->nullable();
            $table->string('label');
            $table->string('type', 15); // basic|allowance|deduction|overtime
            $table->decimal('amount', 12, 2);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('payslip_id')->references('id')->on('payslips')->cascadeOnDelete();
            $table->foreign('salary_component_id')->references('id')->on('salary_components')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslip_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('overtime_records');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('salary_components');
    }
};
