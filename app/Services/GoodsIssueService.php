<?php

namespace App\Services;

use App\Models\GoodsIssue;
use App\Models\GoodsIssueItem;
use App\Models\StockMovement;
use App\Models\User;
use App\Repositories\Contracts\GoodsIssueRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GoodsIssueService
{
    public function __construct(
        private readonly GoodsIssueRepositoryInterface $issues,
        private readonly SequenceService $sequences,
        private readonly InventoryService $inventory,
        private readonly InventoryAccountingIntegrationService $accounting,
    ) {}

    public function create(User $actor, array $data): GoodsIssue
    {
        return DB::transaction(function () use ($actor, $data) {
            $issue = $this->issues->create([
                'tenant_id' => $actor->tenant_id,
                'document_number' => $this->sequences->next($actor->tenant_id, 'goods_issue_number', 'GI'),
                'warehouse_id' => $data['warehouse_id'] ?? $this->inventory->defaultWarehouseFor($actor->tenant)?->id,
                'status' => 'draft',
                'document_date' => $data['document_date'] ?? now()->toDateString(),
                'issued_to' => $data['issued_to'] ?? null,
                'reason' => $data['reason'] ?? null,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            foreach ($data['items'] as $item) {
                GoodsIssueItem::create([
                    'tenant_id' => $issue->tenant_id,
                    'goods_issue_id' => $issue->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            return $issue;
        });
    }

    /** The real warehouse + financial event: moves stock OUT and posts the real expense (Dr Expense / Cr Inventory), valued at each product's cost_price. */
    public function issue(User $actor, GoodsIssue $issue): GoodsIssue
    {
        if ($issue->status !== 'draft') {
            throw new InvalidArgumentException("Goods issue {$issue->document_number} has already been issued.");
        }

        $warehouse = $issue->warehouse ?? $this->inventory->defaultWarehouseFor($actor->tenant);

        if (! $warehouse) {
            throw new InvalidArgumentException('No warehouse is configured for this issue.');
        }

        return DB::transaction(function () use ($actor, $issue, $warehouse) {
            $totalCost = 0.0;

            foreach ($issue->items()->with('product')->get() as $item) {
                $this->inventory->adjustStock(
                    $actor, $item->product, $warehouse, StockMovement::TYPE_OUT, (float) $item->quantity,
                    'goods_issue', $issue->id, $issue->reason ?? "Issued to {$issue->issued_to}"
                );

                $totalCost += (float) $item->quantity * (float) $item->product->cost_price;
            }

            $issue = $this->issues->update($issue, ['status' => 'issued', 'updated_by_user_id' => $actor->id]);

            $this->accounting->postGoodsIssue($actor, $issue, round($totalCost, 2));

            return $issue;
        });
    }
}
