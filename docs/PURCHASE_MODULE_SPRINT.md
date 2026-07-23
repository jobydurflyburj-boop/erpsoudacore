# Purchase Module — Production-Ready Completion Sprint

Brought Purchase from MVP-demo depth up to the audited bar CRM, Sales,
and Inventory already carry — Supplier Bills (Accounts Payable),
Supplier Payments, Debit Notes, Purchase Returns, a dedicated Purchase
Dashboard, and two new Purchase Reports, with real integration into
Inventory (already correct as of the Inventory sprint) and Accounting
(the liability side this project had been missing since Sales and
Inventory built their own equivalents).

---

## The gap this sprint closed

Every prior sprint's roadmap note was explicit: Purchase's remaining
depth gap was narrower than it looked, because Goods Receiving already
moved to Inventory. What was left was exactly what this sprint built —
**the financial obligation itself.** A Purchase Order is just an order;
a Goods Receipt (Inventory's own entity) is the physical event; neither
one creates a liability. `SupplierBill` does, and it's the first place
in this codebase that Accounts Payable becomes real rather than a
seeded, unused chart-of-accounts row.

## What was built

### Supplier Bills — the real liability event
Full CRUD, creatable standalone or `createFromGoodsReceipt()` — bills
against what was *actually received* (quantities and costs from the
Goods Receipt), not the original PO, which may differ from what
actually arrived. Approving posts a real, balanced journal entry
(`Dr Inventory`, `Dr VAT recoverable`, `Cr Accounts Payable`) via the
new `PurchaseAccountingIntegrationService` — mirroring
`SalesAccountingIntegrationService`'s exact pattern and its loud-failure
behavior if a standard account is missing.

### Supplier Payments — real multi-bill allocation
`SupplierPayment` + `SupplierPaymentAllocation`, mirroring
`CustomerPaymentService` exactly: one payment can be allocated across
multiple bills. `SupplierBillService::recalculateStatus()` derives
`approved`/`partial`/`paid` purely from real paid/credited totals. The
single-bill "Record Payment" convenience action on `SupplierBillController`
delegates to the same real allocation path, not a separate mechanism.

### Debit Notes — the Purchase-side mirror of Credit Notes
Issued against a specific bill, validated against that bill's *actual
current* outstanding balance (rejected if it would exceed it — tested
explicitly, same as Sales' Credit Notes). Issuing posts the exact
reversing entry of the original bill posting.

### Purchase Returns
The physical counterpart to Debit Notes: returning goods moves stock
back out to the supplier. When a `supplier_bill_id` is provided,
returning **also auto-generates and issues a matching Debit Note** —
`PurchaseReturnService::returnGoods()` calls `DebitNoteService`
directly, the same reasoning `SalesReturnService` applies: a physical
return should always get a financial counterpart.

### Purchase Dashboard and two new Purchase Reports
`PurchaseDashboardService` — document counts across all five document
types, spend/payments this month, outstanding payables, overdue bill
count — mirrors `SalesDashboardService` exactly.
`ReportService::purchaseBySupplier()` and `agingPayables()` (the exact
bucket structure `agingReceivables()` uses: Current/1-30/31-60/61-90/
90+ days) extend the existing report set.

## Integration — the explicit requirement, made real

**With Inventory**: already correct as of the Inventory sprint's
`GoodsReceipt` redesign — this sprint builds on top of it rather than
touching it again (`createFromGoodsReceipt()` reads from a real,
already-received entity).

**With Accounting**: the new `PurchaseAccountingIntegrationService` —
described above. One deliberate simplification, named directly rather
than left implicit: this simplified chart of accounts nets input and
output VAT through the same `2100` account Sales uses for VAT Payable.
A real KSA chart of accounts would split recoverable input VAT from
payable output VAT into separate accounts; that split is explicitly
deferred, not an oversight — see "still out of scope" below.

## Database — verified for real, standing practice held

All 72 migrations (67 prior + 5 new) run cleanly against real
PostgreSQL via `tools/db-verify/`. RLS confirmed enabled and forced on
every new table (`supplier_bills`, `supplier_payments`,
`supplier_payment_allocations`, `debit_notes`, `purchase_returns`, and
their item tables).

## Frontend

Extended the `/app` console with five new screens: Purchase Dashboard,
Supplier Bills, Supplier Payments, Debit Notes, Purchase Returns — all
built on the same generic-list and shared line-item-builder engines the
Sales and Inventory sprints established, plus a "Bill" action added to
the Goods Receipts screen linking the two modules together in the UI
the way they're linked in the API. Verified with the same real check
every prior sprint has used: the embedded JavaScript (now 1,424 lines)
is extracted and run through `node --check`, not just eyeballed — this
sprint's edit caught nothing broken, but the check ran regardless.

**One mistake caught and fixed mid-sprint, worth naming directly**: a
route-file edit briefly deleted the Inventory module's two report
routes (`stock-by-warehouse`, `inventory-by-category`) while adding
Purchase's. Found via a direct `grep` check before moving on, not
after — both sets of routes are confirmed present in the final file.

## Tests

`PurchaseModuleIntegrationTest` — the core of this sprint's
verification: one full real flow (PO → Goods Receipt → Supplier Bill →
Approve → Payment → Purchase Return → Debit Note) asserting actual
stock levels and actual journal entry balances at every step, plus
targeted tests for debit-note-over-balance rejection, multi-bill
payment allocation, and double-approval rejection.
`PurchaseExtensionTenantIsolationTest` — raw-query cross-tenant
invisibility plus independent per-tenant document numbering.
`PurchaseReportsAndDashboardTest` — real data-shape smoke test for the
dashboard and two new reports.

## RBAC, Validation, Audit Logs

No new permission actions — all four new entities were added to the
existing `purchase` module's `covers` list; the same
`purchase.view/create/edit/delete/approve` grants every role already
had apply automatically. Every new model uses the `Auditable` trait
(module `purchase`). Every Form Request validates tenant-scoped foreign
keys via `Rule::exists(...)->where('tenant_id', ...)`, consistent with
every other module.

## What's still explicitly out of scope

Record-level (own-records-only) scoping — still module-level RBAC.
Split input/output VAT accounts (currently netted through one account,
named above). Purchase requisitions / approval workflows before a PO is
created. Landed cost allocation (freight, duty) onto received inventory
value. Partial billing against a single Goods Receipt. Supplier payment
reversal (only allocating is supported, not reversing a
`SupplierPayment` itself — the same limitation `CustomerPaymentService`
has on the Sales side).
