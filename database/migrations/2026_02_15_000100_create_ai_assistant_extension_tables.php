<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // AI Assistant module completion (second pass): real per-tenant
    // settings, tenant-editable prompt templates, a real usage audit
    // trail distinct from ai_messages (which only covers chat), and
    // real automation suggestions a user can act on or dismiss.
    public function up(): void
    {
        Schema::create('ai_settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->boolean('is_enabled')->default(true);
            $table->string('provider_override', 30)->nullable(); // null = use the platform-level config('ai.provider')
            $table->boolean('insights_enabled')->default(true);
            $table->boolean('notifications_enabled')->default(true);
            $table->boolean('automation_suggestions_enabled')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique('tenant_id');
        });

        Schema::create('ai_prompt_templates', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('key', 60); // e.g. chat_system, dashboard_insights, sales_insights, inventory_insights, financial_insights, crm_insights
            $table->text('content');
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('ai_activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('user_id')->nullable();
            $table->string('feature', 40); // chat|dashboard_insight|sales_insight|inventory_insight|financial_insight|crm_insight|report_summary|automation_suggestion|settings_update|prompt_update
            $table->string('provider', 30)->nullable();
            $table->string('model', 60)->nullable();
            $table->text('summary')->nullable();
            $table->timestamp('created_at');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'feature']);
        });

        Schema::create('ai_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('category', 40); // inventory_reorder|overdue_followup|cash_flow_risk|stale_leads|payroll_reminder
            $table->string('title');
            $table->text('description');
            $table->string('status', 15)->default('open'); // open|dismissed|actioned
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_suggestions');
        Schema::dropIfExists('ai_activity_logs');
        Schema::dropIfExists('ai_prompt_templates');
        Schema::dropIfExists('ai_settings');
    }
};
