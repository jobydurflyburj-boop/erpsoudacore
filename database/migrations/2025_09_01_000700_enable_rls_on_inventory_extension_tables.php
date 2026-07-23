<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'product_categories', 'units', 'brands',
        'stock_transfers', 'stock_transfer_items',
        'stock_adjustments', 'stock_adjustment_items',
        'goods_receipts', 'goods_receipt_items',
        'goods_issues', 'goods_issue_items',
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
