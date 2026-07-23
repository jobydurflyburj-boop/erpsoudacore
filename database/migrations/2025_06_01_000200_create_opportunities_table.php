<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // An Opportunity tracks a specific, quantified deal against an
    // existing Customer — distinct from a Lead (pre-qualification
    // contact) and from the Customer record itself (the ongoing
    // relationship). lead_id is kept for provenance only (which lead,
    // if any, led to this customer relationship existing) — an
    // opportunity does not require an unconverted lead to exist.
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('opportunity_number', 30); // SequenceService-generated, e.g. OP-000123

            $table->string('name'); // deal name, e.g. "Acme — Annual ERP License"
            $table->uuid('customer_id');
            $table->uuid('lead_id')->nullable(); // provenance only

            $table->uuid('stage_id');
            $table->decimal('amount', 14, 2)->nullable();
            $table->unsignedTinyInteger('probability')->default(0); // 0-100, defaults from stage but independently editable
            $table->date('expected_close_date')->nullable();
            $table->timestampTz('closed_at')->nullable();

            $table->uuid('assigned_to_user_id')->nullable();
            $table->string('priority', 10)->default('normal'); // low|normal|high|urgent
            $table->text('notes')->nullable();

            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('updated_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('stage_id')->references('id')->on('opportunity_stages')->restrictOnDelete();
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['tenant_id', 'opportunity_number']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'stage_id']);
            $table->index(['tenant_id', 'assigned_to_user_id']);
            $table->index(['tenant_id', 'expected_close_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
