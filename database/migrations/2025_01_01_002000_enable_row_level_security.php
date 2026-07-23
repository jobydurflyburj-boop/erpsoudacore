<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Second, DB-level enforcement layer beneath the application's
    // `BelongsToTenant` model scope and `ResolveTenant` middleware — see
    // docs/FOUNDATION.md "Tenant isolation, defense in depth". A bug that
    // forgets to scope a query cannot leak cross-tenant data, because
    // Postgres itself refuses rows outside app.tenant_id.
    //
    // TenancyServiceProvider sets app.tenant_id / app.is_super_admin at
    // the start of every request (see app/Providers/TenancyServiceProvider.php)
    // via App\Multitenancy\TenantContext::apply().
    private array $tenantScopedTables = [
        'companies', 'branches', 'departments', 'roles', 'role_permissions',
        'users', 'user_branches', 'refresh_tokens', 'otp_codes',
        'password_histories', 'user_devices', 'audit_logs', 'activity_logs',
    ];

    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION current_tenant_id() RETURNS UUID AS $$
              SELECT NULLIF(current_setting('app.tenant_id', true), '')::UUID;
            $$ LANGUAGE SQL STABLE;

            CREATE OR REPLACE FUNCTION is_super_admin() RETURNS BOOLEAN AS $$
              SELECT COALESCE(current_setting('app.is_super_admin', true), 'false')::BOOLEAN;
            $$ LANGUAGE SQL STABLE;
        SQL);

        foreach ($this->tenantScopedTables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY"); // applies even to the table owner role
            DB::statement("
                CREATE POLICY tenant_isolation ON {$table}
                USING (tenant_id = current_tenant_id() OR is_super_admin())
                WITH CHECK (tenant_id = current_tenant_id() OR is_super_admin())
            ");
        }
    }

    public function down(): void
    {
        foreach ($this->tenantScopedTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
        DB::unprepared('DROP FUNCTION IF EXISTS current_tenant_id(); DROP FUNCTION IF EXISTS is_super_admin();');
    }
};
