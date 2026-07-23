<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Distinct from personal_access_tokens: this is the durable
        // "known devices" record for a user (survives logout), used for
        // "new device" login notifications and an at-a-glance device list
        // that doesn't disappear when a token is revoked.
        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id')->nullable(); // NULL = platform-level (Super Admin) row
            $table->uuid('user_id');
            $table->string('device_fingerprint', 64); // sha256(user_agent + resolved device hints)
            $table->string('device_name')->nullable();
            $table->string('platform')->nullable();
            $table->string('last_ip_address', 45)->nullable();
            $table->timestampTz('first_seen_at');
            $table->timestampTz('last_seen_at');
            $table->boolean('is_trusted')->default(false);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'device_fingerprint']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_devices');
    }
};
