<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keyed by (tenant_id, email) rather than Laravel's default
        // email-only primary key, because email is unique per-tenant, not
        // globally (users table note). See PasswordResetService.
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->uuid('tenant_id');
            $table->string('email');
            $table->string('token');
            $table->timestampTz('created_at')->nullable();

            $table->primary(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
