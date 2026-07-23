<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Production Readiness — Database optimization & indexing. Every
    // report/dashboard/insight service built across prior sprints
    // (ReportService, AnalyticsDashboardService, AiInsightService)
    // runs frequent `whereBetween(<date column>, ...)` queries against
    // these exact columns, none of which had a dedicated index beyond
    // the tenant_id RLS column — a real gap surfaced by reviewing
    // actual query patterns, not a blanket "index everything" pass.
    // employees.employment_status, leads.created_at, and
    // opportunities.stage_id were checked and already have a real
    // index from their original module sprints — not duplicated here.
    public function up(): void
    {
        Schema::table('sales_invoices', function ($table) {
            $table->index(['tenant_id', 'document_date']);
        });
        Schema::table('supplier_bills', function ($table) {
            $table->index(['tenant_id', 'document_date']);
        });
        Schema::table('journal_entries', function ($table) {
            $table->index(['tenant_id', 'entry_date']);
        });
        Schema::table('attendances', function ($table) {
            $table->index(['tenant_id', 'date']);
        });
        Schema::table('leave_requests', function ($table) {
            $table->index(['tenant_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function ($table) { $table->dropIndex('sales_invoices_tenant_id_document_date_index'); });
        Schema::table('supplier_bills', function ($table) { $table->dropIndex('supplier_bills_tenant_id_document_date_index'); });
        Schema::table('journal_entries', function ($table) { $table->dropIndex('journal_entries_tenant_id_entry_date_index'); });
        Schema::table('attendances', function ($table) { $table->dropIndex('attendances_tenant_id_date_index'); });
        Schema::table('leave_requests', function ($table) { $table->dropIndex('leave_requests_tenant_id_start_date_end_date_index'); });
    }
};
