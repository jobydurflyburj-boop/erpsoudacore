<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->uuid('category_id')->nullable()->after('category');
            $table->uuid('unit_id')->nullable()->after('unit');
            $table->uuid('brand_id')->nullable()->after('unit_id');
            $table->string('barcode', 60)->nullable()->after('sku');

            $table->foreign('category_id')->references('id')->on('product_categories')->nullOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->nullOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->nullOnDelete();
            $table->unique(['tenant_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['brand_id']);
            $table->dropColumn(['category_id', 'unit_id', 'brand_id', 'barcode']);
        });
    }
};
