<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Real payment entity, replacing the prior MVP's direct
    // paid_amount-on-invoice bump. A payment can be allocated across
    // one or more invoices (payment_allocations) — an invoice's
    // paid_amount is now DERIVED from the sum of its allocations, not
    // set directly. See CustomerPaymentService.
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('payment_number', 30);
            $table->uuid('customer_id');
            $table->decimal('amount', 14, 2);
            $table->decimal('allocated_amount', 14, 2)->default(0);
            $table->string('payment_method', 20)->default('bank_transfer'); // cash|bank_transfer|card|other
            $table->string('reference')->nullable();
            $table->date('payment_date');
            $table->text('notes')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'payment_number']);
            $table->index(['tenant_id', 'customer_id']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('customer_payment_id');
            $table->uuid('sales_invoice_id');
            $table->decimal('amount', 14, 2);
            $table->timestampTz('created_at');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_payment_id')->references('id')->on('customer_payments')->cascadeOnDelete();
            $table->foreign('sales_invoice_id')->references('id')->on('sales_invoices')->restrictOnDelete();
            $table->index(['tenant_id', 'sales_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('customer_payments');
    }
};
