<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Basic Recruitment (Job Openings -> Candidates -> Applications
    // pipeline) and basic Performance Reviews (cycle-based, manager
    // rates an employee) — deliberately scoped to "basic" per the
    // brief: a real pipeline and a real review record, not an
    // interview-scheduling system or a 360-degree feedback engine.
    public function up(): void
    {
        Schema::create('job_openings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('title');
            $table->uuid('department_id')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 15)->default('open'); // open|closed|on_hold
            $table->unsignedSmallInteger('positions_count')->default(1);
            $table->date('posted_date')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('resume_notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('job_opening_id');
            $table->uuid('candidate_id');
            $table->string('status', 15)->default('applied'); // applied|screening|interview|offered|hired|rejected
            $table->date('applied_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('job_opening_id')->references('id')->on('job_openings')->cascadeOnDelete();
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('performance_review_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status', 15)->default('open'); // open|closed
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('cycle_id');
            $table->uuid('employee_id');
            $table->uuid('reviewer_user_id')->nullable();
            $table->unsignedTinyInteger('rating')->nullable(); // 1-5
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->string('status', 15)->default('draft'); // draft|submitted|acknowledged
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('cycle_id')->references('id')->on('performance_review_cycles')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('reviewer_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'cycle_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('performance_review_cycles');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_openings');
    }
};
