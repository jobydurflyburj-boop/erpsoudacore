<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Same pattern as the foundation's enable_row_level_security
    // migration — every new tenant-owned table from the Platform
    // Administration module gets FORCE RLS + the standard policy before
    // this module is considered done. current_tenant_id()/is_super_admin()
    // already exist (created by the foundation migration).
    private array $tables = [
        'company_settings', 'tasks', 'notifications',
        'notification_preferences', 'push_device_tokens',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement("
                CREATE POLICY tenant_isolation ON {$table}
                USING (tenant_id = current_tenant_id() OR is_super_admin())
                WITH CHECK (tenant_id = current_tenant_id() OR is_super_admin())
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
