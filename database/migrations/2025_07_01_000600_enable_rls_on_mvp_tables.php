<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $tables = [
        'warehouses', 'products', 'stock_levels', 'stock_movements',
        'suppliers', 'purchase_orders', 'purchase_order_items',
        'quotations', 'quotation_items', 'sales_orders', 'sales_order_items',
        'sales_invoices', 'sales_invoice_items',
        'chart_of_accounts', 'journal_entries', 'journal_entry_lines',
        'ai_conversations', 'ai_messages',
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
