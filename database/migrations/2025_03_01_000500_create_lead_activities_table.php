<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // The Lead Activity Timeline. Every meaningful lead event lands here
    // — creation, status change, (re)assignment, and manually logged
    // touchpoints (call/email/whatsapp/note). This is the record-level
    // equivalent of activity_logs (module-wide feed) — a per-lead feed a
    // salesperson actually works from.
    public function up(): void
    {
        Schema::create('lead_activities', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('lead_id');
            $table->uuid('user_id')->nullable(); // actor; null for system-generated entries
            $table->string('type', 20); // created|status_changed|assigned|note|call|email|whatsapp|attachment_added
            $table->text('description');
            $table->jsonb('metadata')->nullable(); // e.g. {"from_status_id":..,"to_status_id":..} or {"from_user_id":..,"to_user_id":..}
            $table->timestampTz('created_at');

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('lead_id')->references('id')->on('leads')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'lead_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_activities');
    }
};
