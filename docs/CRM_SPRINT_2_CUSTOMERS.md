# CRM Sprint 2 — Customers

Selected per `ROADMAP.md`'s "Next sprint" section, which listed three
CRM Sprint 2 candidates (Opportunities, Customers, Quotations) without
picking one — "whichever the business needs first." Rather than build a
half-scoped spread across all three, or block on a business-priority
decision I don't have, I made a decisive call: the roadmap's own
phrasing already flagged **Customers** as the most obvious gap ("a won
lead should become something" — until this sprint, a won lead just sat
there with a status flag and nothing else happened). This sprint closes
exactly that gap as a complete, coherent vertical slice, at the same
scope and rigor as CRM Sprint 1. Opportunities and Quotations remain
open for a future sprint.

---

## What was built

- **Customers** — full CRUD, auto-generated sequential customer numbers
  (`CU-000001`, reusing `SequenceService` exactly as it was designed to
  be reused — the first real proof that decision paid off), account
  manager assignment, credit limit / payment terms (fields a future
  Sales/Invoicing module will need), VAT number for business customers.
- **Customer Activity Timeline** — same pattern as Lead's: system-
  generated (`created`, `converted_from_lead`, `account_manager_changed`)
  plus manually logged touchpoints (call/email/whatsapp/note).
- **Lead → Customer conversion** — `POST /crm/leads/{lead}/convert-to-
  customer`. Deliberately its own service (`LeadConversionService`),
  not a method bolted onto `LeadService` or `CustomerService`, since
  conversion reads and writes both entities and belongs to neither one
  alone. Business rule: only a lead whose status is marked `is_won` can
  convert, and only once — both enforced with real validation, not just
  documented. The new Customer copies the lead's contact data (with
  optional overrides at conversion time), keeps a `source_lead_id` back-
  reference, and the original Lead gets a forward
  `converted_to_customer_id` — both directions are queryable without a
  reverse lookup.
- **Record-level scoping, again** — `Customer::OWN_RECORDS_ONLY_ROLES`
  and a new `CustomerPolicy`, mirroring `LeadPolicy` exactly: Sales sees
  and manages only the customers they're the account manager for.
- **CRM Dashboard extended** — real customer totals, new-this-month, and
  conversions-this-month, scoped the same way lead figures are.
- **CRM Navigation extended** — a Customers entry alongside Leads.

## RBAC

No new permission actions — `customers` and `customer_activities` were
added to the existing `crm` module's `covers` list in
`config/permissions.php` (documentation metadata only); the same
`crm.view`/`crm.create`/`crm.edit`/`crm.delete` grants that already
existed for Leads apply to Customers automatically, for every role that
already has them. No `RoleProvisioningService` changes were needed —
this is exactly the "adding a module is additive" design the foundation
was built for.

## Database, verified for real this time

Every migration in this sprint was run against a real PostgreSQL 16
instance using `tools/db-verify/` (built last sprint) — not left as
"should work." All 44 migrations (39 prior + 5 new) run cleanly in
order. Row-Level Security was confirmed enabled and forced on both new
tables, and a real cross-tenant write was rejected exactly as expected:
`ERROR: new row violates row-level security policy for table
"customers"`. This is now the standing practice for every migration
added to this project, not a one-off exercise.

## Tests

`CustomerManagementTest` (6 cases: creation with sequential numbering,
independent sequences from Leads, full CRUD lifecycle, account-manager-
change timeline logging, Sales-role record scoping), `LeadConversionTest`
(5 cases: happy path with full activity-trail verification, rejecting a
non-won lead, rejecting a double conversion, applying overrides,
rejecting a Sales rep converting a lead that isn't theirs),
`CrmCustomerTenantIsolationTest` (2 cases: raw-query cross-tenant
invisibility, independent per-tenant numbering sequences) —
13 new test cases, all following the exact patterns established in CRM
Sprint 1.

## What's still deferred

Opportunities (a distinct pipeline entity from Leads — quantified,
staged deals) and Quotations remain unbuilt, per the roadmap's original
three-way split. Nothing in this sprint's schema blocks either from
being added later the same way this sprint was added to Sprint 1's
foundation.
