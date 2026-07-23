# CRM Sprint 3 — Opportunities

Selected per `ROADMAP.md`'s "Next sprint" section, which left CRM
Sprint 3 as a two-way choice (Opportunities or Quotations) without a
business-priority pick. Decisive call: **Opportunities**, since
Quotations would need a Product/Item catalog that doesn't exist until
Inventory is built — Opportunities has no such dependency and is the
natural next piece of the pipeline that was already Lead → Customer.

---

## What was built

- **Opportunity Stages** — tenant-editable pipeline stages (same
  pattern as Lead Statuses): `name_en`/`name_ar`, color, a
  `default_probability` a new opportunity inherits unless overridden,
  `is_won`/`is_lost`/`is_default` flags. Seeded with 6 sensible defaults
  (Qualification → Needs Analysis → Proposal → Negotiation → Closed
  Won/Lost) at registration, via `CrmProvisioningService` — extended
  this sprint with a **backfill-safe path**: a tenant that registered
  before this sprint existed already has `lead_sources` rows, which
  would otherwise short-circuit the whole provisioning method: a second,
  independent existence check now lets `crm:provision-defaults` add
  *just* the missing opportunity stages without re-provisioning (and
  duplicating) sources/statuses that tenant already has.
- **Opportunities** — a distinct entity from both Lead (pre-
  qualification contact) and Customer (the ongoing relationship):
  `customer_id` required, `lead_id` kept only for provenance. Full CRUD,
  auto-generated sequential numbers (`OP-000001`, `SequenceService`
  reused for the third time now), amount, probability (defaults from
  the stage, independently editable), expected close date, assignment.
- **Business rule, enforced not just documented**: moving an
  opportunity into a stage marked `is_won` or `is_lost` automatically
  sets `closed_at` and logs a dedicated `won`/`lost` timeline event —
  `OpportunityService::handleStageChange()` is the one place this
  happens, so it can't be bypassed by updating the stage through a
  different code path.
- **Opportunity Activity Timeline** — same shape as Lead's and
  Customer's: system-generated (`created`, `stage_changed`, `assigned`,
  `won`, `lost`) plus manually logged touchpoints.
- **`OpportunityPolicy`** — record-level scoping, third and exact repeat
  of the `LeadPolicy`/`CustomerPolicy` pattern: Sales sees/manages only
  opportunities assigned to them.
- **CRM Dashboard extended**: real open-pipeline count, win rate,
  won/lost this month, open pipeline value (amount × probability,
  summed), and a full stage-by-stage breakdown with per-stage deal
  count and total amount — the same honest, real-data-only approach
  every dashboard in this project has followed.
- **CRM Navigation extended** with an Opportunities entry.

## RBAC

No new permission actions — `opportunities`, `opportunity_stages`, and
`opportunity_activities` were added to the existing `crm` module's
`covers` list (documentation metadata only). The same
`crm.view`/`crm.create`/`crm.edit`/`crm.delete` grants every role
already had for Leads and Customers apply automatically. No
`RoleProvisioningService` changes needed — third consecutive CRM sprint
to confirm the "adding a module is additive" design holds.

## Database, verified for real — and the tool caught a real gap this time

Every migration was run against real PostgreSQL via `tools/db-verify/`
before being considered done, per standing practice. **This run actually
failed on the first attempt** — not because of a bug in the migration,
but because the `opportunities` migration was the first in this project
to call `$table->date('expected_close_date')`, a Blueprint method the
verification shim didn't implement yet (its method coverage was built
by enumerating only the *original* 39 migrations' calls). Real Laravel
has `date()`; the hand-built shim didn't yet. Fixed by adding the one
missing method, then re-verified clean: all 48 migrations (44 prior + 4
new) run in order, RLS confirmed enabled and forced on all three new
tables, and a real cross-tenant write against `opportunities` was
rejected exactly as expected. Documented here rather than silently
fixed, consistent with how every other real issue found in this project
has been handled — and it's a useful data point: the verification tool
itself needs incremental maintenance as the schema grows, the same way
any other piece of this codebase does.

## Tests

`OpportunityManagementTest` (5 cases: default stage provisioning,
creation with probability inherited from stage, full CRUD, the
won-stage-sets-closed-at business rule, Sales-role record scoping),
`OpportunityStageManagementTest` (3 cases: default-stage exclusivity,
in-use protection, default-stage deletion protection),
`CrmOpportunityTenantIsolationTest` (2 cases: raw-query cross-tenant
invisibility, independent per-tenant numbering) — 10 new test cases,
all following CRM Sprint 1/2's established patterns exactly.

## What's still deferred

Quotations remains the one CRM Sprint 2/3 candidate not yet built —
needs a Product/Item catalog (Inventory) first to be meaningful.
Meetings/calendar and CRM-specific Reports also remain unbuilt. Sales,
Purchase, Inventory, Accounting, HR, Payroll, Reports, Billing, AI
Assistant, and Settings remain at zero code — see this sprint's updated
`ROADMAP.md` for why a full twelve-module MVP could not be completed in
a single sprint and what a realistic path through the remaining modules
looks like.
