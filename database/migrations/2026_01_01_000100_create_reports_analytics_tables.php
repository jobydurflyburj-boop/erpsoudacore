<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Reports & Analytics completion: the Custom Report Builder saves a
    // real, re-runnable definition (source + columns + filters + an
    // optional group-by) rather than a one-off query — `source` is
    // deliberately a closed enum (see CustomReportService::ALLOWED_SOURCES)
    // resolved against an allow-listed column map at run time, never
    // raw SQL, so a saved definition can never become an injection
    // vector no matter what a tenant stores in it. Scheduled Reports
    // references either a saved Custom Report or one of the built-in
    // report keys (e.g. 'income_statement') via `report_key`.
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('source', 40);
            $table->jsonb('columns');
            $table->jsonb('filters')->nullable();
            $table->string('group_by')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'source']);
        });

        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('name');
            $table->string('report_key', 60); // e.g. 'income_statement', 'vat_report', or a custom_reports.id
            $table->uuid('custom_report_id')->nullable();
            $table->string('frequency', 15); // daily|weekly|monthly
            $table->string('format', 10)->default('csv'); // csv|pdf
            $table->jsonb('recipients'); // array of email addresses
            $table->boolean('is_active')->default(true);
            $table->timestamp('next_run_at');
            $table->timestamp('last_run_at')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('custom_report_id')->references('id')->on('custom_reports')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('custom_reports');
    }
};
