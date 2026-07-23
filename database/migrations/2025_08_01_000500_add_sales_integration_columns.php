<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // CRM integration: a Quotation can now originate directly from an
    // Opportunity, not just be entered fresh against a Customer.
    // Accounting integration: journal_entries gains a source reference
    // so auto-posted entries (from invoice issuance, payments, credit
    // notes) are traceable back to the sales document that caused them
    // — the same reference_type/reference_id pattern stock_movements
    // already uses.
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->uuid('opportunity_id')->nullable()->after('customer_id');
            $table->foreign('opportunity_id')->references('id')->on('opportunities')->nullOnDelete();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('source_type')->nullable()->after('memo'); // 'sales_invoice'|'customer_payment'|'credit_note'|null (manual)
            $table->uuid('source_id')->nullable()->after('source_type');
            $table->index(['tenant_id', 'source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id']);
        });
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropForeign(['opportunity_id']);
            $table->dropColumn('opportunity_id');
        });
    }
};
