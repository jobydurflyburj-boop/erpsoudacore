# Sales Module — Production-Ready Completion Sprint

Brought the Sales module from MVP-demo depth (one module of six built to
a reduced bar, per `MVP_DEMO.md`) up to the audited bar every CRM
sprint carried — the specific candidate my own prior roadmap named as
"the strongest candidate... most likely to be scrutinized in a
sales-facing demo." This sprint completes it: Quotations, Sales Orders,
Delivery Notes, Sales Invoices, Customer Payments, Credit Notes, Sales
Returns, a dedicated Sales Dashboard, and three new Sales Reports — plus
real integration with CRM, Inventory, and Accounting, not just
coexistence in the same codebase.

---

## The central architectural decision this sprint made

The MVP sprint had Invoice issuance move stock out — a shortcut that
conflates two genuinely different events. This sprint corrects it:

- **Delivery Notes are now the real warehouse event.** Stock moves out
  when a Delivery Note is delivered, not when an invoice is issued. A
  Delivery Note can be created from a confirmed Sales Order or stand
  alone.
- **Invoices are now purely financial.** Issuing one posts a real
  journal entry; it no longer touches `stock_levels` at all.

This is the correct ERP domain model — a services invoice shouldn't
move stock; a delivery should; and the two don't always happen at the
same time in a real business. `SalesInvoiceService::issue()` was
rewritten accordingly, and `SalesModuleIntegrationTest` asserts
explicitly that stock changes exactly once (at delivery) and not again
at invoice issuance — this was a real design bug in the MVP sprint,
caught and fixed here, not discovered later.

## What was built

### Delivery Notes
Full CRUD, creatable standalone or from a confirmed Sales Order
(`DeliveryNoteService::createFromSalesOrder`). `deliver()` is the real
inventory-affecting event — moves every line item's stock out via
`InventoryService`, rejects double-delivery.

### Customer Payments — a real entity, not a field bump
The MVP sprint recorded payment by directly incrementing
`sales_invoices.paid_amount`. This sprint replaces that with a genuine
`CustomerPayment` + `PaymentAllocation` model: a payment is recorded
once and can be allocated across one or more invoices.
`SalesInvoiceService::recalculateStatus()` derives `draft`/`issued`/
`partial`/`paid` purely from the real paid/credited totals, so status
never drifts out of sync with the numbers that actually determine it.
The old single-invoice "Record Payment" button on an Invoice still
works — it's now a convenience wrapper (`CustomerPaymentService::
payInvoice()`) over the same real path, not a separate mechanism.

### Credit Notes
Issued against a specific invoice, validated against that invoice's
*actual current* outstanding balance (rejected if it would exceed it —
tested explicitly). Issuing posts the exact reversing journal entry of
the original invoice posting and reduces the invoice's `credited_amount`
— a credit note is modeled as reducing the obligation, not as a
payment, which is the correct accounting treatment.

### Sales Returns
The physical counterpart to Delivery Notes: receiving a return moves
stock back in. When linked to an invoice, receiving a return **also
auto-generates and issues a matching Credit Note** —
`SalesReturnService::receive()` calls `CreditNoteService` directly. This
was a deliberate design choice: leaving the credit note as a manual
follow-up step is exactly how returns silently never get credited in
real operations.

### Sales Dashboard
Its own service (`SalesDashboardService`), distinct from the global
Platform Admin dashboard and the CRM dashboard: document counts across
all six document types, quotation win rate, this month's revenue and
payments, outstanding receivables, and a real overdue-invoice count.

### Sales Reports — three new, real reports
`salesByCustomer()`, `salesByProduct()`, and a genuine aging-receivables
report (Current / 1-30 / 31-60 / 61-90 / 90+ days, computed from actual
`due_date`s against today, not a placeholder bucket structure).

## Integration — the explicit requirement, made real

**With Inventory**: already real from the MVP sprint (stock movements),
now correctly re-homed onto Delivery Notes and Sales Returns as
described above.

**With Accounting**: the new `SalesAccountingIntegrationService` posts a
real, balanced journal entry for every financial event — invoice issued
(Dr AR / Cr Revenue / Cr VAT Payable), payment received (Dr Cash / Cr
AR), credit note issued (the reversing entry) — against the tenant's
actual seeded chart of accounts. `journal_entries` gained
`source_type`/`source_id` columns so every auto-posted entry is
traceable back to the sales document that caused it. If a tenant has
renamed or deleted a required standard account, posting fails loudly
with a clear message — silently skipping would be worse than a loud
failure here.

**With CRM**: `quotations.opportunity_id` — a Quotation can now
originate from a real Opportunity, not just be entered fresh against a
Customer. (A full items-copying "convert Opportunity to Quotation"
wasn't built: Opportunities don't carry itemized product data to copy,
only a deal amount — so the honest integration is linking, not a fake
auto-conversion that would have to invent line items.)

## Database — verified for real, standing practice held

All 60 migrations (54 prior + 6 new) run cleanly against real
PostgreSQL via `tools/db-verify/`. RLS confirmed enabled and forced on
all five new tables (`delivery_notes`, `customer_payments`,
`payment_allocations`, `credit_notes`, `sales_returns`), spot-checked.

## Frontend

Extended the `/app` console with five new screens (Sales Dashboard,
Delivery Notes, Payments, Credit Notes, Sales Returns) using the same
generic-list and custom-render patterns already established. Added a
genuine new verification step this sprint: the embedded JavaScript is
now extracted and run through `node --check` before being considered
done, not just visually reviewed — a real syntax check the MVP sprint's
frontend work didn't have.

## Tests

`SalesModuleIntegrationTest` — the core of this sprint's verification:
one full real flow (Opportunity → Quotation → Order → Delivery →
Invoice → Payment → Return → Credit Note) asserting actual stock levels,
actual journal entry balances, and actual invoice balance-due at every
step, plus three focused tests (double-delivery rejection, multi-invoice
payment allocation, credit-note-exceeds-balance rejection).
`SalesExtensionTenantIsolationTest` — raw-query cross-tenant invisibility
for the new tables, following the exact pattern every prior sprint's
isolation tests used. `SalesReportsAndDashboardTest` — real data-shape
smoke test for the five new reporting endpoints.

## RBAC, Validation, Audit Logs

No new permission actions needed — `delivery_notes`, `customer_payments`,
`payment_allocations`, `credit_notes`, `sales_returns` were added to the
existing `sales` module's `covers` list; the same `sales.view/create/
edit/delete` grants every role already had apply automatically. Every
new model uses the `Auditable` trait (module `sales`), consistent with
every other model in this codebase. Every Form Request validates
tenant-scoped foreign keys via `Rule::exists(...)->where('tenant_id', ...)`,
the same pattern used everywhere else.

## What's still explicitly out of scope

Record-level (own-records-only) scoping for the Sales module — still
module-level RBAC, same as every module built in the MVP sprint. PDF
generation for any of these six document types. ZATCA e-invoicing.
Partial delivery (a Delivery Note currently delivers a Sales Order's
full line-item quantities; splitting one order across multiple partial
deliveries isn't supported). Refunding a payment (only allocating and
crediting are supported, not reversing a `CustomerPayment` itself).
