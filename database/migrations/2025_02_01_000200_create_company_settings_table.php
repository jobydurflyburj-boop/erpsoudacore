<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Company Profile (previous migration) holds the fixed, named fields
    // from the brief. Company Settings is deliberately a small, extensible
    // key/value store for everything else (formatting, feature toggles,
    // working-day defaults) that doesn't warrant its own column and will
    // keep growing as future modules add their own settings keys — each
    // key is namespaced by module (e.g. 'admin.week_start_day') so a
    // future CRM/Sales settings key can't collide with this module's.
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('company_id');
            $table->string('key', 100);
            $table->jsonb('value');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['company_id', 'key']);
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
