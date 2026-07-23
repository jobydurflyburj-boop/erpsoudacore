<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Found during the tenant-isolation review: failed_login_attempts and
    // password_reset_tokens both carry tenant_id but were left out of the
    // original enable_row_level_security migration, on the reasoning that
    // nothing reads them back broadly today (LoginRateLimiter uses Redis
    // for the actual throttle decision; these tables are audit trails).
    // That reasoning holds only as long as no future admin endpoint ever
    // queries them without remembering to filter by tenant — which is
    // exactly the kind of assumption RLS exists to make unnecessary.
    // Closing the gap now rather than leaving it latent.
    public function up(): void
    {
        DB::statement('ALTER TABLE failed_login_attempts ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE failed_login_attempts FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY tenant_isolation ON failed_login_attempts
            USING (tenant_id = current_tenant_id() OR is_super_admin())
            WITH CHECK (tenant_id = current_tenant_id() OR is_super_admin() OR tenant_id IS NULL)
        ");
        // WITH CHECK allows tenant_id IS NULL on write specifically for
        // this table: a failed login against a subdomain that doesn't
        // resolve to any tenant at all must still be recordable (that's
        // itself a signal worth capturing), and there is no tenant
        // session to bind in that case.

        DB::statement('ALTER TABLE password_reset_tokens ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE password_reset_tokens FORCE ROW LEVEL SECURITY');
        DB::statement("
            CREATE POLICY tenant_isolation ON password_reset_tokens
            USING (tenant_id = current_tenant_id() OR is_super_admin())
            WITH CHECK (tenant_id = current_tenant_id() OR is_super_admin())
        ");
    }

    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON failed_login_attempts');
        DB::statement('ALTER TABLE failed_login_attempts DISABLE ROW LEVEL SECURITY');
        DB::statement('DROP POLICY IF EXISTS tenant_isolation ON password_reset_tokens');
        DB::statement('ALTER TABLE password_reset_tokens DISABLE ROW LEVEL SECURITY');
    }
};
