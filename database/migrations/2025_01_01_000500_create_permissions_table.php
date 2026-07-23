<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Global catalog — NOT tenant-scoped. The catalog of what CAN be
        // granted is platform-wide (seeded from config/permissions.php);
        // which permissions a given role actually HAS is tenant-scoped,
        // via role_permissions below. This split is what lets every
        // tenant define custom roles from the same permission catalog
        // without the catalog itself being duplicated per tenant.
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('module', 40);   // 'admin' | 'core' | future: 'crm', 'sales', ...
            $table->string('action', 20);   // view|create|edit|delete|approve|export|print
            $table->string('name')->unique(); // 'admin.users.view' machine key
            $table->string('label');          // human-readable, for role-builder UI
            $table->timestamps();

            $table->unique(['module', 'action', 'name']);
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
