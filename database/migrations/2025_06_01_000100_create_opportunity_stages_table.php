<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // CRM Sprint 3 — Opportunities. Same pattern as lead_statuses:
    // tenant-editable pipeline stages, seeded with defaults at
    // registration (CrmProvisioningService), not hardcoded.
    public function up(): void
    {
        Schema::create('opportunity_stages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('color', 7)->default('#6B7280');
            $table->unsignedTinyInteger('default_probability')->default(0); // 0-100, the stage's typical close probability
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'name_en']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_stages');
    }
};
