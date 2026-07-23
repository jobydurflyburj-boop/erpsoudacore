<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The users.role_id -> roles.id FK was added in the users migration
    // already; this migration adds the reverse convenience index used by
    // CheckPermission's hot-path lookup (role_id -> its permissions).
    public function up(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->index(['role_id', 'permission_id'], 'role_permissions_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropIndex('role_permissions_lookup_idx');
        });
    }
};
