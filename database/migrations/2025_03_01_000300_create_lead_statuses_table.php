<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->string('color', 7)->default('#6B7280'); // hex, for pipeline/kanban UI
            $table->boolean('is_won')->default(false);   // marks the pipeline's "closed won" stage(s)
            $table->boolean('is_lost')->default(false);  // marks the pipeline's "closed lost" stage(s)
            $table->boolean('is_default')->default(false); // the status a new lead starts in
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
        Schema::dropIfExists('lead_statuses');
    }
};
