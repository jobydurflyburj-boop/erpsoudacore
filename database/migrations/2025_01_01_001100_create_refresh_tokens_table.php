<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Complements Sanctum's short-lived access tokens (config/sanctum.php
        // 'expiration'). One refresh_tokens row per device session, rotated
        // on every use (TokenService::refresh) — a reused/revoked token
        // being presented again revokes the whole family for that user,
        // per docs/FOUNDATION.md "Authentication".
        Schema::create('refresh_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id')->nullable(); // NULL = platform-level (Super Admin) row
            $table->uuid('user_id');
            $table->uuid('personal_access_token_id')->nullable(); // the access token this refresh issued most recently
            $table->string('token_hash', 64)->unique(); // sha256 of the opaque token — never store plaintext
            $table->uuid('family_id'); // shared across a rotation chain, used to revoke-on-reuse
            $table->boolean('remember_me')->default(false);
            $table->string('device_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestampTz('expires_at');
            $table->timestampTz('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['user_id', 'revoked_at']);
            $table->index('family_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
