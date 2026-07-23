# Inventory Module — Production-Ready Completion Sprint

Brought Inventory from MVP-demo depth (Products, Warehouses, and basic
Stock only) up to the audited bar CRM and Sales already carry —
Categories, Units, Brands, Barcode Support, Stock Transfers, Stock
Adjustments, Goods Receiving, Goods Issue, Low Stock Alerts, and a
richer set of Inventory Reports, with real integration into Sales,
Purchase, and Accounting.

---

## The central architectural decision this sprint made

Same pattern as the Sales sprint's Delivery Note correction, applied
here to Purchase: **`PurchaseOrderService::receive()` no longer moves
stock directly.** It now creates and receives a real `GoodsReceipt` —
the Inventory-side warehouse event — the same physical/financial
separation Delivery Notes established for Sales. This makes Goods
Receiving genuinely reusable: a receipt can now also be recorded
standalone (stock received with no PO at all), which the old
PO-only code path couldn't do. `PurchaseMvpTest` gained a case proving
receiving a PO creates a real, linked `goods_receipts` row and a
`stock_movements` row with `reference_type = 'goods_receipt'`, not a
direct, untracked movement.

## What was built

### Categories, Units, Brands — real entities, not strings
The MVP sprint's `products.category`/`products.unit` were plain
strings. This sprint adds real tenant-editable `product_categories`,
`units`, and `brands` tables with full CRUD, plus `category_id`/
`unit_id`/`brand_id` foreign keys on `products`. The old string columns
are kept (nullable, unused going forward) rather than dropped, so nothing
from the MVP or Sales sprints breaks.

### Barcode Support — real, not cosmetic
`products.barcode`, unique per tenant. `InventoryService::findByBarcode()`
and a real `GET /inventory/products-by-barcode?barcode=...` endpoint —
the actual lookup a POS-style or warehouse-scanning workflow needs, not
just a stored field nobody can query by.

### Stock Transfers
Move stock between two warehouses atomically —
`StockTransferService::complete()` moves the source warehouse's stock
out and the destination's stock in in one transaction, both legs
protected by `InventoryService::adjustStock()`'s own negative-stock
guard. Rejects transferring a warehouse to itself and rejects
completing twice.

### Stock Adjustments — a real approval workflow, not a bare endpoint
The MVP sprint's `POST /inventory/stock-adjustments` (kept, unchanged,
for simple one-line corrections) is now joined by a real, auditable
`StockAdjustment` entity: multi-line, reasoned, with a draft→approved
workflow. Approving applies every line's signed `quantity_change` to
stock and **posts a real accounting entry** valued at each product's
`cost_price` — a write-off is `Dr Operating Expenses / Cr Inventory`;
found stock is the reverse.

### Goods Receiving and Goods Issue
`GoodsReceipt` (real stock-in, described above) and `GoodsIssue` (real
stock-out for internal consumption/samples/damage, distinct from a
customer-facing Sales Delivery Note). Issuing goods posts a real
`Dr Expense / Cr Inventory` entry, valued the same way Stock Adjustments
are.

### Low Stock Alerts — a real notification, not just a report row
`InventoryService::adjustStock()` now checks, after any stock decrease,
whether the product's total stock crossed at or below its reorder
point — and if so, fires a real notification (`NotificationService`) to
every user with the Inventory or Company Owner role. This isn't a
"low stock" flag someone has to remember to check — it fires from the
same one place every stock decrease in the whole product already flows
through (adjustments, transfers, goods issues, sales deliveries,
returns), so nothing can decrease stock without the alert logic seeing it.

### Inventory Reports — extending the MVP sprint's valuation report
`stockByWarehouse()` (quantity and product count per warehouse) and
`inventoryByCategory()` (quantity and value grouped by category,
including an honest "Uncategorized" bucket for products without one) —
alongside the existing inventory valuation and low-stock count from the
MVP sprint.

## Integration — the explicit requirement, made real

**With Purchase**: the central redesign above — Goods Receipts are now
the real event, not a Purchase-owned side effect.

**With Sales**: unchanged and still correct — Delivery Notes and Sales
Returns already moved real stock via the same `InventoryService`; this
sprint didn't need to touch that path, only verify it still works
alongside the new Low Stock Alert hook (confirmed in the integration
test below).

**With Accounting**: new `InventoryAccountingIntegrationService`,
mirroring `SalesAccountingIntegrationService`'s exact pattern — Stock
Adjustments and Goods Issues both post real, balanced journal entries
against the tenant's actual chart of accounts (`1200 Inventory`,
`5100 Operating Expenses`), traceable via `journal_entries.source_type`/
`source_id`. Goods Receipts deliberately do **not** post here — receiving
against a PO is a liability event (Accounts Payable), which belongs to
Purchase-side accounting integration, explicitly out of scope for this
sprint (see below).

## Database — verified for real, standing practice held

All 67 migrations (60 prior + 7 new) run cleanly against real
PostgreSQL via `tools/db-verify/`. RLS confirmed enabled and forced on
every new table (`product_categories`, `units`, `brands`,
`stock_transfers`, `stock_adjustments`, `goods_receipts`,
`goods_issues`, and their item tables).

## Frontend

Extended the `/app` console with ten new screens: Categories, Units,
Brands, Warehouses (now full CRUD, was create-only before), Stock
Transfers, Stock Adjustments, Goods Receipts, Goods Issues — plus a
shared line-item builder (`addSimpleItemRow`/`collectSimpleItems`)
reused across all four new document types, the same "build one engine,
configure it per entity" approach the MVP and Sales sprints established.
Products now shows brand, barcode, and a low-stock warning indicator
inline. Verified with the same real check the Sales sprint introduced:
the embedded JavaScript is extracted and run through `node --check`
before being considered done.

## Tests

`InventoryModuleIntegrationTest` — the core of this sprint's
verification: one full real flow (Category/Unit/Brand/Barcode →
second Warehouse → Goods Receipt → Stock Transfer → Stock Adjustment
with accounting assertions → Goods Issue with accounting assertions →
Low Stock Alert firing) asserting actual stock levels and actual
journal entry balances at every step, plus targeted tests for
same-warehouse-transfer rejection, double-completion rejection, and
default-warehouse-deletion protection. `InventoryExtensionTenantIsolationTest`
— raw-query cross-tenant invisibility plus independent per-tenant
document numbering. `InventoryReportsTest` — real data-shape smoke test
for the two new reports. `PurchaseMvpTest` gained one case proving the
redesigned receive flow creates a real linked Goods Receipt.

## RBAC, Validation, Audit Logs

No new permission actions — all eleven new entities were added to the
existing `inventory` module's `covers` list; the same
`inventory.view/create/edit/delete` grants every role already had apply
automatically. Every new model uses the `Auditable` trait (module
`inventory`). Every Form Request validates tenant-scoped foreign keys
via `Rule::exists(...)->where('tenant_id', ...)`, consistent with every
other module.

## What's still explicitly out of scope

Record-level (own-records-only) scoping — still module-level RBAC.
Purchase-side accounting integration (Accounts Payable posting on Goods
Receipt). Multi-warehouse costing methods (FIFO/weighted average — all
valuation currently uses each product's single `cost_price`). Batch/lot
and serial number tracking. Barcode generation/printing (lookup only,
not label creation). Partial goods receiving against a single PO line.
