# Platform Administration Module

Built directly on the Tenancy + Auth + RBAC foundation — no architecture
changes, only new modules following the exact same repository/service/
controller/request/resource shape already established. This is the
module every future ERP module (CRM, Sales, Inventory, Accounting,
HR/Payroll) will itself be built against, the same way this module was
built against the foundation.

---

## Scope delivered

Dashboard, Company Profile, Company Settings, Branch Management,
Department Management, User Management (extended), Role & Permission
Management (extended), Notification Center, Activity Log (extended),
Profile & Preferences (already covered by the foundation's `/me`
endpoints + this module's notification preferences).

## New tables (all RLS-protected — `FORCE ROW LEVEL SECURITY`, same
policy shape as the foundation)

`company_settings`, `tasks`, `notifications`, `notification_preferences`,
`push_device_tokens`. Plus column additions to `companies`, `branches`,
`departments`, and `activity_logs`. See the `2025_02_01_*` migrations.

---

## Dashboard — no fake data

The brief lists widgets for Revenue, Expenses, Customers, Leads, Sales
Orders, Purchase Orders, Inventory Summary, and Today's Attendance —
every one of these belongs to a module explicitly out of scope for this
pass (CRM, Sales, Purchase, Inventory, Accounting, HR/Payroll). Rather
than fabricate numbers or quietly build shadow versions of those modules,
`DashboardService` returns each of these as:

```json
{ "available": false, "module": "sales", "message": "This widget will populate once the sales module is installed." }
```

The frontend renders this as an honest "not yet available" state. Five
widgets ARE real today, backed by this module's own tables:
- **Employees** — active user count.
- **Pending Approvals** — invited-but-not-yet-active users (a real
  pending action, reframed rather than faked — cross-module approval
  workflows like sales-order approval don't exist yet).
- **Tasks** — pending/overdue/due-today counts from the new `tasks`
  table (a deliberately minimal personal task list, not a project
  management module).
- **Subscription Status** — from `tenants.status`/`trial_ends_at`,
  already in the foundation.
- **Recent Activities**, **Quick Actions**, **System Health** — all
  genuinely computed (activity feed query, permission-filtered action
  catalog, live DB/cache/queue connectivity checks).

All four charts (Monthly Revenue, Monthly Sales, Expenses, Customer
Growth) are deferred the same way, for the same reason.

## Company Profile vs. Company Settings

Deliberately two different storage shapes. **Company Profile** is the
brief's fixed, named field list (legal name, VAT number, timezone,
currency, etc.) — real columns on `companies`, validated by dedicated
Form Requests. **Company Settings** is a small, extensible key/value
store (`company_settings`, one row per key) for anything else
(formatting preferences, feature toggles) that will keep growing as
future modules add their own settings — avoids a companies table with
dozens of nullable columns for settings that only matter to specific
modules.

## Activity Log — one feed, two purposes

The foundation's `Auditable` trait wrote only to `audit_logs` (field-level
diffs, compliance record). This module extends it to ALSO write a
matching `activity_logs` entry with module attribution and a parsed
browser string — one model event, two tables, two different audiences
(compliance diff vs. human-readable feed). Two things were fixed while
wiring this up, both worth noting:
- **Password hashes were excluded from both.** `getChanges()` includes
  `password` on a password-change update; storing even a hashed password
  in an audit trail is bad practice regardless of hashing — now redacted
  explicitly (`Auditable::$auditExcludedFields`).
- **Silent field touches (`last_login_at`, etc.) don't spam the activity
  feed** — they're still captured in `audit_logs`' diff, but skipped from
  `activity_logs` since an explicit, more meaningful event
  (`auth.login`) already covers that moment.

`module` is inferred from the event name's own namespace prefix
(`auth.login` → `auth`) when not passed explicitly, so existing
foundation call sites (`AuthService`, `CheckPermission`) didn't need to
be touched individually to gain module attribution.

## Notification Center

In-app persistence is unconditional (every notification lands in the
recipient's inbox). Email/SMS/WhatsApp/push are additional, gated by
per-(user, category, channel) preferences — absence of a preference row
means "use the default" (email ON, everything else OFF, since SMS/
WhatsApp/push carry a real per-message cost a tenant should opt into).
SMS/WhatsApp/push transport are `TODO(ops)` gaps in the exact same shape
as the foundation's OTP SMS gap — the preference resolution, in-app
persistence, and dispatch structure are all real and tested; only the
external gateway credentials are a deployment-time concern.

Wired to two real trigger points so it isn't a disconnected service:
task assignment (`TaskService::create`) and role changes
(`UserService::changeRole`).

## Bugs found and fixed while building this module

Two instances of the exact pivot-table `tenant_id` bug the foundation's
`TENANT_ISOLATION_REVIEW.md` found and fixed for `role_permissions`
turned up again here — worth naming explicitly rather than burying in a
diff:
- `UserService::invite()`'s original `$user->branches()->sync($ids)` call
  (present since the foundation, not something this module introduced,
  but only now exercised by a test) wrote `user_branches.tenant_id` as
  `NULL` — invisible under RLS. Fixed alongside the new
  `UserService::assignBranches()` method, both routed through a shared
  `syncBranchesWithTenant()` helper that always passes `tenant_id`
  explicitly in the pivot data.
- A first draft of `UserController::update`/`DepartmentController::update`
  reused the `Store*Request` Form Requests (which require `company_id`),
  which would have rejected any partial `PATCH` that didn't resupply it.
  Caught by `BranchDepartmentTest` before shipping — fixed with dedicated
  `UpdateBranchRequest`/`UpdateDepartmentRequest` classes, matching the
  `Store`/`Update` split already used for `Company`.

## What's still deferred

Same boundary as before: no CRM/Sales/Purchase/Inventory/Accounting/
HR/Payroll routes, tables, or models. Dashboard widgets and charts for
those modules are wired to display real zero/unavailable states now,
ready to switch to live data the moment each module lands — no dashboard
changes will be needed then, only removing the corresponding entry from
`DashboardService::DEFERRED_WIDGETS`/`DEFERRED_CHARTS`.
