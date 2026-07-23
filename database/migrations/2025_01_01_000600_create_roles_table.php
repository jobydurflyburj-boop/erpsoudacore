<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            // NULL tenant_id = platform-level role (Super Admin only).
            // Every tenant role below is created per-tenant at registration
            // time from RoleSeeder's defaults and is independently editable
            // from then on — see docs/FOUNDATION.md "RBAC".
            $table->uuid('tenant_id')->nullable();
            $table->string('code', 40);      // 'super_admin' | 'company_owner' | ... | custom
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->boolean('is_system_role')->default(false); // seeded default vs tenant-created
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
