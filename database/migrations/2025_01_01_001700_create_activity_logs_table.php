<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Distinct from audit_logs: this is behavioral/session activity
        // (login, logout, password change, role change, permission
        // denial) rather than data mutation history — the feed a Company
        // Owner reviews for "what happened in my account", not a
        // field-level diff.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id')->nullable(); // NULL = platform-level (Super Admin) row
            $table->uuid('user_id')->nullable();
            $table->string('event', 60); // 'auth.login' | 'auth.logout' | 'auth.password_changed' | 'rbac.permission_denied' | ...
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->jsonb('context')->nullable();
            $table->timestampTz('created_at');

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->index(['tenant_id', 'event', 'created_at']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
