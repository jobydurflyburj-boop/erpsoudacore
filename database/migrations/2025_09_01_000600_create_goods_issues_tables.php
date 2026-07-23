<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The counterpart to Goods Receipt: manual stock OUT for reasons
    // other than a sale — internal consumption, samples, damage
    // write-off. Distinct from a Sales Delivery Note (customer-facing)
    // and from a Stock Adjustment (a correction to a miscount, not a
    // deliberate issuance).
    public function up(): void
    {
        Schema::create('goods_issues', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('document_number', 30);
            $table->uuid('warehouse_id');
            $table->string('status', 15)->default('draft'); // draft|issued|cancelled
            $table->date('document_date');
            $table->string('issued_to')->nullable(); // free text: department, project, purpose
            $table->text('reason')->nullable();
            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('updated_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('warehouse_id')->references('id')->on('warehouses')->restrictOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'document_number']);
        });

        Schema::create('goods_issue_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('goods_issue_id');
            $table->uuid('product_id');
            $table->decimal('quantity', 14, 3);

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('goods_issue_id')->references('id')->on('goods_issues')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_issue_items');
        Schema::dropIfExists('goods_issues');
    }
};
