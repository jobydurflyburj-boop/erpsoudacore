<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Quotation -> Sales Order -> Invoice, each a real document with its
    // own number sequence and line items. Items are managed as a
    // replace-all-on-update set (simpler than sub-resource CRUD) — a
    // deliberate MVP scope cut, not a bug; see docs/MVP_DEMO.md.
    public function up(): void
    {
        foreach (['quotations' => 'QT', 'sales_orders' => 'SO', 'sales_invoices' => 'INV'] as $table => $prefix) {
            Schema::create($table, function (Blueprint $t) use ($table) {
                $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $t->uuid('tenant_id');
                $t->string('document_number', 30);
                $t->uuid('customer_id');

                if ($table === 'sales_orders') {
                    $t->uuid('quotation_id')->nullable();
                }
                if ($table === 'sales_invoices') {
                    $t->uuid('sales_order_id')->nullable();
                    $t->date('due_date')->nullable();
                    $t->decimal('paid_amount', 14, 2)->default(0);
                }

                $t->string('status', 15)->default('draft');
                $t->date('document_date');
                $t->decimal('subtotal', 14, 2)->default(0);
                $t->decimal('vat_amount', 14, 2)->default(0);
                $t->decimal('total', 14, 2)->default(0);
                $t->text('notes')->nullable();
                $t->uuid('created_by_user_id')->nullable();
                $t->uuid('updated_by_user_id')->nullable();
                $t->timestamps();
                $t->softDeletes();

                $t->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $t->foreign('customer_id')->references('id')->on('customers')->restrictOnDelete();
                $t->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
                $t->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
                $t->unique(['tenant_id', 'document_number']);
                $t->index(['tenant_id', 'status']);
            });

            Schema::create($table === 'quotations' ? 'quotation_items' : ($table === 'sales_orders' ? 'sales_order_items' : 'sales_invoice_items'), function (Blueprint $t) use ($table) {
                $t->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
                $t->uuid('tenant_id');
                $t->uuid(rtrim($table, 's').'_id'); // quotation_id / sales_order_id / sales_invoice_id
                $t->uuid('product_id');
                $t->string('description')->nullable();
                $t->decimal('quantity', 14, 3);
                $t->decimal('unit_price', 12, 2);
                $t->decimal('vat_rate', 5, 2)->default(15.00);
                $t->decimal('line_total', 14, 2);

                $t->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $t->foreign(rtrim($table, 's').'_id')->references('id')->on($table)->cascadeOnDelete();
                $t->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
            });
        }

        Schema::table('sales_orders', function (Blueprint $t) {
            $t->foreign('quotation_id')->references('id')->on('quotations')->nullOnDelete();
        });
        Schema::table('sales_invoices', function (Blueprint $t) {
            $t->foreign('sales_order_id')->references('id')->on('sales_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('sales_invoices');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('quotations');
    }
};
