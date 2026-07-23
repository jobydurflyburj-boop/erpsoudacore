<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Same pattern as lead_activities — the ongoing-relationship timeline
    // for a Customer, distinct from the pre-conversion Lead timeline
    // (which stays on the original Lead record even after conversion,
    // so the full history — including how the relationship started —
    // is never lost).
    public function up(): void
    {
        Schema::create('customer_activities', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('customer_id');
            $table->uuid('user_id')->nullable();
            $table->string('type', 20); // created|converted_from_lead|account_manager_changed|note|call|email|whatsapp
            $table->text('description');
            $table->jsonb('metadata')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_activities');
    }
};
