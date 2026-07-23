# Super Admin Console — Sprint

Chosen as this sprint's target per `docs/ROADMAP.md`'s "Next sprint"
section: the only candidate there that was already concretely scoped
(list tenants, view platform metrics, suspend/reactivate a tenant)
without requiring a fresh business-priority decision the way "CRM
Sprint 2" or a new ERP domain would. CRM Sprint 1 was confirmed
complete (per `ROADMAP.md`'s own "Completed" section) before this
sprint started, per this sprint's own instruction to check first.

---

## What was missing before this sprint

Super Admin had an *identity* (platform-level `users` rows, a role
holding every permission, a separate login endpoint) since the
Foundation sprint, but no actual console capability — confirmed during
the prior audit (`FEATURE_MATRIX.md`: "zero Super Admin console routes
exist"). This sprint closes exactly that gap, nothing more.

## Backend

- **New middleware, `EnsureSuperAdmin`**: gates the console route group.
  Deliberately checks `TenantContext::isSuperAdmin()` rather than a
  `permission:module.action` check — Super Admin isn't tenant-RBAC-scoped,
  so a permission check would be redundant with (and weaker than)
  checking the identity class directly.
- **New route group** in `routes/api.php`, structurally separate from
  the tenant-scoped authenticated group: `['auth:sanctum',
  'tenant.bind_authenticated', 'ensure.super_admin']`, deliberately
  *without* `tenant.active` — that middleware would reject every Super
  Admin request outright, since no tenant is ever bound for this
  identity. `bootstrap/app.php` had a comment anticipating exactly this
  since the Foundation sprint.
- **`SuperAdminTenantService`**: suspend/reactivate as real state
  transitions, not bare `UPDATE`s — suspending a tenant now (a) revokes
  every active session for every user in that tenant immediately, not
  just blocks future logins, and (b) logs to *both* the platform's own
  audit trail and the affected tenant's own `activity_logs`, so a
  Company Owner can see in their own activity feed why they were locked
  out rather than it happening invisibly from their side.
- **`PlatformMetricsService`**: tenant counts by status, total users,
  total leads, new tenants this month, a 6-month signup trend — every
  figure a real cross-tenant count, reachable only because a Super
  Admin session has `is_super_admin=true` (the same RLS bypass every
  other platform-level query in this codebase uses). **No revenue/MRR
  figure is reported** — there is no billing engine in this codebase
  (confirmed in the prior audit), and reporting one would violate this
  project's standing "no fake data" rule the same way the tenant-facing
  dashboards already honor it for their own deferred widgets.
- **New migration**: `tenants.suspension_reason` +
  `tenants.suspended_by_user_id` — suspension now records who and why,
  not just a bare status flag.

## Frontend — a deliberately narrow decision, not a framework rollout

Every prior sprint's audit noted the same honest gap: no frontend
framework exists anywhere in this repository. This sprint was the first
to explicitly require one. Rather than make a project-wide frontend
architecture decision unilaterally (React vs. Vue vs. a build toolchain
— a decision with much larger blast radius than one sprint), the choice
made here is deliberately the smallest one that satisfies the
requirement honestly:

**One static Blade view (`resources/views/super-admin/console.blade.php`),
no build step, vanilla JS calling the same `/api/v1` JSON endpoints via
`fetch()` with a bearer token — exactly the way any other API consumer
would.** This required one small, genuine addition to `bootstrap/app.php`
(`routes/web.php`, previously absent since the product had no
server-rendered surface at all) — documented inline as scoped to this
one page, not a general-purpose web layer.

This is a real, functional console — login, live metrics, a tenant
table with working suspend/reactivate actions — not a mockup. It is
explicitly **not** a precedent for how the rest of the product's
frontend should be built; that remains an open decision for whoever
scopes the next customer-facing frontend work.

## Tests

`SuperAdminConsoleTest` (10 cases): non-super-admin 403, cross-tenant
listing, real metrics, suspend blocks login, suspend revokes existing
sessions, suspending one tenant doesn't touch another, reactivate
flow, reactivating a non-suspended tenant is rejected (409), the
tenant-side activity log entry, and the console page itself renders.
`PlatformMetricsServiceTest` (unit) verifies the status-grouping logic
directly against a real RLS-bound query.

## A bug caught during this sprint

`User::withoutGlobalScope('tenant')->where(...)->each(...)` was written
first — Eloquent query builders don't have an `each()` method (that's a
`Collection` method); this would have been a hard runtime error the
first time tenant suspension actually ran. Caught by re-reading the code
rather than by execution (this project still has never been run — see
`AUDIT_REPORT.md`), fixed to `->get()->each(...)`. Logged here rather
than silently fixed, consistent with how prior sprints have surfaced
bugs found along the way rather than only reporting clean results.
