# SoudaCore ERP — Client-Ready MVP Demo

Built to a different bar than every prior sprint in this project: breadth
over exhaustive depth, explicitly requested to produce a demoable MVP in
a single pass rather than another fully-audited module sprint. What that
tradeoff means concretely, stated up front rather than discovered later:

- **Test coverage is smoke-level, not exhaustive.** One consolidated
  end-to-end test per new module (the critical business logic path —
  VAT calculation, stock movement correctness, debit=credit validation,
  document conversion rules), not the full authorization/tenant-isolation
  matrix every CRM sprint carried. The underlying architecture (RLS,
  RBAC, Repository/Service pattern) is identical to every prior module —
  only the *test volume* was cut back, not the security model.
- **Record-level scoping (Sales-only-sees-own) was not extended** to
  Sales/Purchase/Inventory/Accounting — every role with module access
  sees all records in that module. Extending the CRM ownership pattern
  here is a real V2 item, not an oversight.
- **The frontend is one functional console**, not a polished product UI.
  It is real — every button calls a real endpoint, every number is a
  real query result — but visual design was scoped for "looks credible
  in a demo," not for a design system.

---

## What was built this sprint

### Backend — six new modules, all following the exact existing architecture

**Inventory** — Products, Warehouses, Stock Levels, Stock Movements.
Every stock change (`InventoryService::adjustStock`) is atomic — updates
`stock_levels` and records a `stock_movements` row in one transaction,
with row-level locking (`lockForUpdate`) so concurrent adjustments can't
race, and a hard rejection if a movement would take stock negative.

**Purchase** — Suppliers, Purchase Orders with line items.
`PurchaseOrderService::receive()` is the real inventory-affecting event:
receiving a PO moves stock **in** at the tenant's default warehouse via
`InventoryService`, not just a status flip. Rejects double-receiving.

**Sales** — Quotations → Sales Orders → Invoices, a real three-stage
document chain, not three independent lists:
- `QuotationService`, `SalesOrderService::createFromQuotation()`,
  `SalesInvoiceService::createFromSalesOrder()` — each conversion copies
  line items forward and enforces a real precondition (a quotation must
  be `accepted` to convert; an order must be `confirmed` to invoice).
- `SalesInvoiceService::issue()` moves stock **out** — the sales-side
  mirror of a PO receipt.
- `SalesInvoiceService::recordPayment()` derives `paid`/`partial` status
  from the actual paid amount, rejects overpayment.
- VAT is computed per line at that line's own rate (`CalculatesDocumentTotals`
  trait, shared across all four document types — Quotation/Order/Invoice/PO),
  not a single flat assumption.

**Basic Accounting** — a seeded, real chart of accounts (9 standard
accounts: Cash, AR, Inventory, AP, VAT Payable, Equity, Revenue, COGS,
Operating Expenses) + manual journal entries. `AccountingService::createEntry()`
enforces genuine double-entry validation: total debits must equal total
credits, and every line must have exactly one of debit/credit non-zero —
rejected outright, never auto-corrected.

**Reports** — four real cross-module reports (`ReportService`): Sales
summary, Purchase summary, Inventory valuation, Trial Balance. Every
figure is a live query, consistent with every dashboard in this project.

**AI Assistant (basic)** — real, not fabricated, and explicitly **not**
an LLM. `AiAssistantService` matches a handful of keywords (leads,
customers, opportunities/pipeline, sales/invoices, stock/inventory) and
answers with genuinely computed numbers from the tenant's own data.
Conversation history persists (`ai_conversations`/`ai_messages`). Wiring
a real LLM provider is the definitive V2 item here — seeAPI below.

Registration (`RegistrationService`) now also provisions a default
warehouse and the default chart of accounts, alongside the CRM defaults
from prior sprints — every new tenant gets a fully working demo-ready
setup immediately, no manual setup steps.

### RBAC

Six new permission modules (`inventory`, `purchase`, `sales`,
`accounting`, `reports`, `ai`) added to `config/permissions.php` and
granted to sensible default roles in `RoleProvisioningService` —
Accounting is deliberately Owner/Admin/Accountant-only (the most
sensitive module), AI Assistant is available to every seat (a personal
productivity tool, not sensitive data).

### Database — verified for real, same standing practice

All 54 migrations (48 prior + 6 new) run cleanly against real
PostgreSQL 16 via `tools/db-verify/`, RLS confirmed enabled and forced
on every new table, spot-checked against real cross-tenant write
rejection on `sales_invoices`, `journal_entries`, `products`, and
`purchase_orders`.

### Frontend — the first full tenant-facing console (`/app`)

One static page (`resources/views/app/console.blade.php`), same
architecture decision as the Super Admin Console (vanilla JS, no build
step, talks to the real `/api/v1` JSON API via `fetch()` with a bearer
token) — now extended to cover the whole product rather than one
platform-operator screen. Built around two reusable engines rather than
15 bespoke screens:

1. A **generic list+create view** (`renderListView`) — configuration-
   driven (columns, create-form fields), used for Leads, Customers,
   Opportunities, Suppliers, Products, Chart of Accounts.
2. A **generic document view** (`renderDocList`) — same idea, extended
   with a dynamic line-item builder (product picker, quantity, price,
   live subtotal/VAT/total preview) and per-status workflow actions
   (Convert to Order, Convert to Invoice, Receive, Issue, Record
   Payment), used for Quotations, Sales Orders, Invoices, Purchase
   Orders.

Plus purpose-built screens where a generic table didn't fit: Dashboard
(real metric cards pulled from every module's own reporting endpoint),
Stock (levels + movement history), Journal Entries (debit/credit line
builder), Reports, AI Assistant (chat UI), Users/Roles (read views over
existing Platform Admin endpoints), Company Settings (edit form).

**Login UX**: a public `GET /public/tenants/lookup?subdomain=X`
endpoint (new, minimal, discloses nothing not already effectively
public) lets the login screen accept a company subdomain rather than
requiring the raw tenant UUID — this sandbox doesn't do real subdomain
routing, so without this the demo login would be impractical. An inline
registration form is built into the login screen so a fresh evaluator
can self-register a demo company without touching the API directly.

---

## How to run the demo

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
```

Then visit `/app` — either register a new company via the link on the
login screen, or use the seeded demo tenant (`DemoTenantSeeder`,
local/testing environments only): subdomain `demo`, email
`owner@demo.soudacore.app`, password `DemoPassword!123`.

**Reminder carried from every prior sprint's documentation**:
`composer install` cannot run in this sandbox (Packagist blocked) — the
above has not been executed end-to-end here. The database layer has
been (`tools/db-verify/`); the application/HTTP layer, including this
new frontend actually rendering against a live app, has not. Run this
in an environment with real internet access before a live client demo,
not for the first time in front of the client.

---

## Version 2 Backlog

Everything below was deliberately deferred to hit the MVP timeline —
named explicitly rather than silently dropped:

**Depth on what shipped:**
- Record-level (own-records-only) scoping for Sales, Purchase,
  Inventory, and Accounting — currently module-level only.
- Multi-warehouse stock transfers, batch/serial number tracking,
  automated reorder alerts/purchase suggestions.
- Auto-posting journal entries from Sales/Purchase documents (currently
  fully manual — Accounting doesn't yet know a Sales Invoice happened).
- A KSA-specific chart-of-accounts template wired to ZATCA VAT reporting
  (current COA is generic).
- ZATCA Phase 2 e-invoicing (clearance/reporting, QR codes, hash
  chaining) — referenced throughout this project's history but never
  built into this Laravel codebase.
- Quotation/Order/Invoice PDF generation and printing.
- Full CRUD for Warehouses, Journal Entry editing/reversal, Chart of
  Account hierarchy management in the frontend (currently create-only
  or read-only in the UI for some of these).

**Genuinely new capability:**
- A real LLM-backed AI Assistant (provider selection is a business
  decision, not an engineering one — see prior sprints' notes).
- HR and Payroll modules (payroll needs GOSI computation rules as real
  business input).
- Billing & Subscription engine (plans, payment gateway, automated
  trial/suspension) — Super Admin Console can already suspend/reactivate
  manually; this would automate it.
- Meetings/calendar in CRM, Opportunities→Quotation direct linking.
- A generated OpenAPI spec for the full, now much larger, endpoint
  surface.

**Frontend:**
- A deliberate, project-wide frontend framework decision (this MVP
  console remains a scoped, functional build — not a precedent for a
  polished product UI, same caveat every prior sprint's frontend work
  carried).
- Arabic RTL, dark mode, mobile responsiveness — none implemented in
  this console.
- Real-time updates (currently everything is request/response, no
  websockets/polling).

**Ops, unchanged from every prior sprint's assessment:**
- Application-layer execution in a real environment (the single most
  important open item across this entire project).
- CI pipeline, monitoring, backups, secrets management, production
  deployment target.
- Archiving the four superseded pre-Laravel documents (still open,
  unaddressed across five sprints now).
