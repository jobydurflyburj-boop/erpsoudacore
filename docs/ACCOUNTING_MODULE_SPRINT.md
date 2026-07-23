# Accounting Module — Production-Ready Completion Sprint

Brought Accounting's own core engine up to the audited bar CRM, Sales,
Inventory, and Purchase already carry — real journal entry reversal,
split input/output VAT accounts (closing a gap the Sales and Purchase
sprints each named directly rather than hid), and two real financial
statements (Income Statement, Balance Sheet) computed from actual
journal entry lines, not placeholder numbers.

---

## Why Accounting, and why now

Every prior sprint's roadmap note pointed here for a reason stronger
than "it's next in line": Sales and Purchase have both been posting
real, balanced journal entries into this module for two sprints
running, while the module's own core — manual entry creation, Trial
Balance, and nothing else — never got the same depth treatment. A
chart of accounts and journal entries that can be created but never
corrected, and a Trial Balance with no Income Statement or Balance
Sheet standing behind it, was the shallowest remaining piece of an
otherwise closed accounting loop.

## What was built

### Real journal entry reversal
`AccountingService::reverseEntry()` — the manual-entry engine's
missing piece since Foundation. A reversed entry is never edited or
deleted in place (that would break the audit trail every other part of
this project takes seriously); instead a brand-new entry is created
with every line's debit and credit swapped, and the original is marked
`is_reversed` and linked to the new entry via `reversed_by_entry_id`.
**A deliberate restriction, named directly rather than glossed over**:
auto-posted entries (anything with `source_type` set — from Sales,
Purchase, or Inventory integrations) cannot be reversed through this
endpoint. Correcting those means correcting the document that caused
them (issue a credit note, a debit note, a stock adjustment) — not
editing the accounting side in isolation, which would desynchronize
the books from the document that's supposed to explain them. Tested
explicitly: a real manual entry reverses cleanly and balances; a
second reversal attempt is rejected; an auto-posted sales invoice entry
is rejected outright.

### Split input/output VAT accounts
The Sales and Purchase sprints each named this as a deliberate,
temporary simplification rather than an oversight: input tax
(recoverable, paid on purchases) and output tax (payable, collected on
sales) were netting through the same `2100` account. This sprint adds
a real `2110 VAT Recoverable` account (an asset) and repoints
`PurchaseAccountingIntegrationService` to post input VAT there — Sales'
`SalesAccountingIntegrationService` is unchanged and correctly
continues posting output VAT to `2100`. New tenants get both accounts
automatically at registration; a `accounting:provision-defaults`
console command (mirroring `crm:provision-defaults` exactly) backfills
the new account for tenants that registered before this sprint,
without duplicating the nine accounts they already have — the same
guarded-existence-check pattern CRM Sprint 3 used to backfill
Opportunity Stages.

### Two real financial statements
`ReportService::incomeStatement(?from, ?to)` — revenue accounts use
their normal credit balance (credit − debit), expense accounts their
normal debit balance (debit − credit), with an optional date range;
omitted, it covers all-time. `ReportService::balanceSheet()` — an
as-of-today snapshot (a balance sheet is a point-in-time statement by
its nature, unlike the Income Statement, so it takes no period
parameter); assets carry a normal debit balance, liabilities and
equity a normal credit balance, and retained earnings (net income to
date) rolls into equity so the sheet actually balances — a real
accounting requirement enforced with a `balanced` boolean in the
response (tolerance 0.01), not assumed.

## Database — verified for real, standing practice held

One new migration: `is_reversed` (boolean) and `reversed_by_entry_id`
(nullable, self-referencing FK) added to `journal_entries`. All 73
migrations (72 prior + 1 new) run cleanly against real PostgreSQL via
`tools/db-verify/`.

## Frontend

Extended the Accounting nav group with Income Statement and Balance
Sheet screens, and added a Reverse action to the Journal Entries list —
visible only on manual entries that aren't already reversed (auto-
posted and already-reversed entries show their status instead, not a
button that would just fail). Verified the same way every prior
sprint's frontend work has been: the embedded JavaScript (now 1,471
lines) extracted and run through `node --check`, and grepped for Blade
`{{` collisions (none).

## Tests

`AccountingModuleIntegrationTest` (6 cases): a new tenant gets both VAT
accounts; the backfill command adds the missing account to an existing
tenant without duplicating others; Purchase now posts input VAT to its
own account; a manual entry reverses cleanly with swapped, balanced
lines and a rejected second reversal; an auto-posted sales invoice
entry cannot be reversed directly; the Income Statement and Balance
Sheet reflect real posted sales activity (revenue, net income, and a
balanced sheet with VAT-inclusive AR). `AccountingExtensionTenantIsolationTest`
(2 cases): raw-query invisibility of a journal entry across tenants,
and independent per-tenant journal entry numbering across an entry-
then-reversal sequence. `AccountingStatementsReportsTest` (1 case):
real data-shape smoke test for both new endpoints, including a
zero-activity tenant's balance sheet actually balancing (all zeros)
and the date-range filter not erroring on an empty range.

## RBAC, Validation, Audit Logs

No new permission actions — reversal uses the existing
`accounting.edit` grant; the two new report endpoints use the existing
Reports view grant. `JournalEntry` already carried the `Auditable`
trait; reversal creates a fully audited new entry rather than mutating
history.

## What's still explicitly out of scope

Accounting periods / period-close (a locked prior period would reject
new postings into it — not built). Multi-currency. Budget vs. actual
reporting. A KSA-specific COA template wired to ZATCA e-invoicing
(ZATCA work belongs to the earlier, separate single-file HTML
application, not this Laravel codebase). Record-level (own-records-
only) scoping — still module-level RBAC. Editing a posted entry in
place (by design — see the reversal restriction above). Statement
export (PDF/Excel) or scheduled generation.
