<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Supports the Super Admin Console sprint: suspending/reactivating a
    // tenant now records who did it and why, not just the bare status
    // flag that already existed. tenants has no RLS (platform-level
    // table, by design since the foundation) so these columns are
    // readable the same way the rest of the row already is.
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('suspension_reason')->nullable()->after('suspended_at');
            $table->uuid('suspended_by_user_id')->nullable()->after('suspension_reason');

            $table->foreign('suspended_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['suspended_by_user_id']);
            $table->dropColumn(['suspension_reason', 'suspended_by_user_id']);
        });
    }
};
