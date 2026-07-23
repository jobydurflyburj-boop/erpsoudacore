<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id')->nullable(); // NULL = platform-level (Super Admin) row
            $table->uuid('company_id')->nullable();
            $table->uuid('default_branch_id')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('role_id')->nullable();

            // citext column type set at DB level (see note below) so
            // email comparisons/uniqueness are case-insensitive without
            // application-layer lower()'ing everywhere.
            $table->string('email');
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('password');
            $table->string('full_name');
            $table->string('avatar_path')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('preferred_locale', 5)->default('ar');
            $table->string('timezone', 64)->default('Asia/Riyadh');
            $table->string('status', 20)->default('active'); // active|invited|disabled
            $table->timestampTz('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestampTz('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            $table->foreign('default_branch_id')->references('id')->on('branches')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();

            // Email is unique PER TENANT, not globally — the same address
            // can be an owner at one company and an invited employee at
            // another. This is why tenant resolution must happen before
            // any auth lookup (see ResolveTenant middleware).
            $table->unique(['tenant_id', 'email']);
            $table->index('status');
        });

        // Case-insensitive email comparisons at the DB level.
        DB::statement('ALTER TABLE users ALTER COLUMN email TYPE citext');

        // The (tenant_id, email) unique constraint above does NOT catch
        // duplicate emails among platform-level rows (tenant_id IS NULL —
        // Super Admins), because SQL treats NULL <> NULL. This partial
        // index closes that gap specifically for the NULL-tenant case.
        DB::statement('CREATE UNIQUE INDEX users_platform_email_unique ON users (email) WHERE tenant_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
