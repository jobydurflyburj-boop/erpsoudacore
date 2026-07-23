<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('user_id'); // recipient
            $table->string('category', 60); // 'user.invited' | 'role.changed' | 'approval.pending' | ...
            $table->string('title');
            $table->text('body')->nullable();
            $table->jsonb('data')->nullable(); // structured payload for the frontend (e.g. a deep link target)
            $table->timestampTz('read_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['tenant_id', 'user_id', 'read_at']);
        });

        // One row per (tenant, user, category, channel) — sparse table:
        // absence of a row for a given category means "use the default",
        // which NotificationService resolves at send time. This is
        // simpler and less error-prone than a wide table with one column
        // per channel and NULL meaning "not set".
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('user_id');
            $table->string('category', 60);
            $table->string('channel', 20); // in_app|email|sms|whatsapp|push
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'category', 'channel']);
        });

        // Device push tokens (FCM/APNs) — the registration endpoint and
        // model are real; actual push delivery is a TODO(ops) transport
        // gap the same way OTP SMS is, since it needs real FCM/APNs
        // credentials this environment doesn't have.
        Schema::create('push_device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('user_id');
            $table->string('token', 255);
            $table->string('platform', 20); // ios|android|web
            $table->timestampTz('last_used_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_device_tokens');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
