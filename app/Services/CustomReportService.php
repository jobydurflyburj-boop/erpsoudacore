<?php

namespace App\Services;

use App\Models\CustomReport;
use App\Models\Employee;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Product;
use App\Repositories\Contracts\CustomReportRepositoryInterface;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\SalesInvoice;
use App\Models\SupplierBill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * The Custom Report Builder: a saved, re-runnable definition (source +
 * columns + filters + optional group-by) executed safely at run time.
 * The entire safety model is one allow-list (`SOURCES`) — a source key
 * maps to a real Eloquent model and a fixed set of real column names;
 * nothing outside that list is ever accepted, so a saved definition
 * can never become a SQL-injection vector no matter what a tenant
 * stores in `filters`/`columns`/`group_by`. Every value is still bound
 * through Eloquent's query builder (never string-interpolated SQL) as
 * a second layer of safety on top of the allow-list.
 */
class CustomReportService
{
    /** source key => [model class, allowed columns] */
    private const SOURCES = [
        'sales_invoices' => [SalesInvoice::class, ['id', 'document_number', 'status', 'document_date', 'due_date', 'subtotal', 'vat_amount', 'total', 'paid_amount']],
        'supplier_bills' => [SupplierBill::class, ['id', 'document_number', 'status', 'document_date', 'due_date', 'subtotal', 'vat_amount', 'total', 'paid_amount']],
        'journal_entries' => [JournalEntry::class, ['id', 'entry_number', 'entry_date', 'memo', 'source_type', 'is_reversed']],
        'employees' => [Employee::class, ['id', 'employee_number', 'full_name', 'employment_status', 'hire_date', 'basic_salary']],
        'products' => [Product::class, ['id', 'sku', 'name_en', 'cost_price', 'sale_price', 'reorder_point', 'is_active']],
        'customers' => [Customer::class, ['id', 'customer_number', 'company_name', 'first_name', 'last_name', 'email', 'phone', 'customer_type']],
        'leads' => [Lead::class, ['id', 'lead_number', 'company_name', 'first_name', 'last_name', 'email', 'phone', 'created_at']],
        'opportunities' => [Opportunity::class, ['id', 'opportunity_number', 'name', 'amount', 'probability', 'expected_close_date']],
    ];

    private const OPERATORS = ['=', '!=', '>', '<', '>=', '<=', 'like'];

    public function __construct(private readonly CustomReportRepositoryInterface $reports) {}

    public function sources(): array
    {
        return collect(self::SOURCES)->map(fn ($def) => $def[1])->toArray();
    }

    public function create(User $actor, array $data): CustomReport
    {
        $this->validateDefinition($data['source'], $data['columns'], $data['filters'] ?? [], $data['group_by'] ?? null);

        return $this->reports->create(array_merge($data, [
            'tenant_id' => $actor->tenant_id, 'created_by_user_id' => $actor->id,
        ]));
    }

    public function update(CustomReport $report, array $data): CustomReport
    {
        $source = $data['source'] ?? $report->source;
        $columns = $data['columns'] ?? $report->columns;
        $filters = $data['filters'] ?? $report->filters ?? [];
        $groupBy = array_key_exists('group_by', $data) ? $data['group_by'] : $report->group_by;

        $this->validateDefinition($source, $columns, $filters, $groupBy);

        return $this->reports->update($report, $data);
    }

    private function validateDefinition(string $source, array $columns, array $filters, ?string $groupBy): void
    {
        if (! isset(self::SOURCES[$source])) {
            throw new InvalidArgumentException("Unknown report source '{$source}'.");
        }
        [, $allowedColumns] = self::SOURCES[$source];

        foreach ($columns as $col) {
            if (! in_array($col, $allowedColumns, true)) {
                throw new InvalidArgumentException("Column '{$col}' is not available for source '{$source}'.");
            }
        }

        foreach ($filters as $filter) {
            if (! in_array($filter['column'] ?? null, $allowedColumns, true)) {
                throw new InvalidArgumentException("Filter column '".($filter['column'] ?? '?')."' is not available for source '{$source}'.");
            }
            if (! in_array($filter['operator'] ?? null, self::OPERATORS, true)) {
                throw new InvalidArgumentException("Filter operator '".($filter['operator'] ?? '?')."' is not supported.");
            }
        }

        if ($groupBy !== null && ! in_array($groupBy, $allowedColumns, true)) {
            throw new InvalidArgumentException("Group-by column '{$groupBy}' is not available for source '{$source}'.");
        }
    }

    /** Executes a saved report definition and returns real result rows. */
    public function run(CustomReport $report): array
    {
        [$modelClass] = self::SOURCES[$report->source];
        $query = $modelClass::query();

        foreach (($report->filters ?? []) as $filter) {
            $query->where($filter['column'], $filter['operator'], $filter['value']);
        }

        if ($report->group_by) {
            return $query->select($report->group_by, DB::raw('count(*) as total'))
                ->groupBy($report->group_by)->orderByDesc('total')->get()->toArray();
        }

        return $query->select($report->columns)->limit(1000)->get()->toArray();
    }
}
