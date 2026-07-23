<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('cr_number')->nullable();      // Commercial Registration number
            $table->string('vat_number', 20)->nullable();  // ZATCA 15-digit VAT number
            $table->string('industry')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('is_default')->default(true);  // most tenants have exactly one company
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
