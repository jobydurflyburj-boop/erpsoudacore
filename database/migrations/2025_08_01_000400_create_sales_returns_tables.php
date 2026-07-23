<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // A Sales Return is the physical event (goods coming back) — it can
    // optionally generate a linked Credit Note (the financial event),
    // same separation of concerns as Delivery Note (physical) vs.
    // Invoice (financial).
    public function up(): void
    {
        Schema::create('sales_returns', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('document_number', 30);
            $table->uuid('customer_id');
            $table->uuid('sales_invoice_id')->nullable();
            $table->uuid('warehouse_id')->nullable();
            $table->uuid('credit_note_id')->nullable();
            $table->string('status', 15)->default('draft'); // draft|received|cancelled
            $table->date('document_date');
            $table->text('reason')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices')->nullOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->nullOnDelete();
            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'document_number']);
        });

        Schema::create('sales_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('sales_return_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(15.00);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('sales_return_id')->references('id')->on('sales_returns')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_return_items');
        Schema::dropIfExists('sales_returns');
    }
};
