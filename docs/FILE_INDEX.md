# SoudaCore ERP — File Index

Every file below was inspected directly for this audit. To keep 264
files readable, each table states a **default answer** once; a file
only gets a Notes entry when it deviates from that default.

**Default for every file in this index unless noted:** Should it be
kept? **Yes.** Can it be deleted? **No.** Required by another file?
**Yes** — through the dependency chain implied by its layer (a
Controller requires its Requests/Resources/Services; a Service requires
its Repositories; a Repository requires its Model; a Model requires its
migration to have run) and wired centrally in
`RepositoryServiceProvider` (repositories) or `bootstrap/app.php`
(middleware) or `routes/api.php` (controllers). Dependencies aren't
repeated per-row for this reason — the layer tells you the shape.

---

## app/Models/ (27 files)

| File | Purpose |
|---|---|
| `Tenant.php` | The subscribing account. Root of tenant isolation — every other tenant-owned model's `tenant_id` ultimately points here. Not RLS-protected itself (it IS the tenant). |
| `Company.php` | Legal entity under a tenant (Company Profile). |
| `CompanySetting.php` | Extensible key/value settings store per company. |
| `Branch.php` | Physical location under a company (GPS, working hours, manager). |
| `Department.php` | Org unit under a company. |
| `Permission.php` | Global catalog row (`module.action`) — not tenant-scoped by design. |
| `Role.php` | Tenant-scoped (or platform-scoped when `tenant_id` is NULL — Super Admin) role; holds the `MFA_REQUIRED_ROLES`-independent permission cache logic. |
| `RolePermission.php` | Explicit pivot model for `role_permissions`, wired via `Role::permissions()->using(...)` — exists specifically to fix the tenant_id-on-pivot-write bug found during CRM Sprint 1. |
| `User.php` | The core identity model — Sanctum tokens, tenant scope, MFA-required derivation, all relations to devices/tasks/notifications. |
| `UserBranch.php` | Pivot for multi-branch user access. |
| `RefreshToken.php` | Rotating refresh-token session record — the "device session" backing store. |
| `OtpCode.php` | MFA code storage, plus the opaque `ticket_hash` used to avoid exposing raw user IDs (see `TENANT_ISOLATION_REVIEW.md`). |
| `PasswordHistory.php` | Last-N password hashes, for reuse prevention. |
| `PasswordResetToken.php` | Composite `(tenant_id, email)`-keyed reset token — Laravel's default broker doesn't fit since email isn't globally unique here. |
| `FailedLoginAttempt.php` | Audit trail for failed logins (the actual throttle decision is Redis-backed via `LoginRateLimiter`, not this table). |
| `UserDevice.php` | Durable "known devices" record, distinct from `RefreshToken` (session-scoped). |
| `AuditLog.php` | Field-level diff compliance record, written by the `Auditable` trait. |
| `ActivityLog.php` | Human-readable behavioral feed (module + browser attribution), also written by `Auditable`. |
| `Task.php` | Personal productivity task — backs the Dashboard's Tasks widget. |
| `Notification.php` | In-app notification inbox row. |
| `NotificationPreference.php` | Per-(user, category, channel) opt-in/out. |
| `PushDeviceToken.php` | Registered push token — real storage, delivery transport is a TODO. |
| `Lead.php` | The CRM Sprint 1 core entity — every field from the brief. |
| `LeadSource.php`, `LeadStatus.php` | Tenant-editable catalogs, same pattern as `Role`. |
| `LeadActivity.php` | Per-lead timeline entry. |
| `LeadAttachment.php` | File attached to a lead. |

## app/Models/Concerns/ (3 files)

| File | Purpose |
|---|---|
| `HasUuid.php` | UUID PK convention — Postgres generates the value, not PHP. |
| `BelongsToTenant.php` | App-layer tenant global scope + auto-fill on create. Convenience layer; RLS is the real boundary. |
| `Auditable.php` | Opt-in trait wiring model events to both `AuditLogService` and `ActivityLogService`, with sensitive-field redaction (`password`) and noisy-field suppression (`last_login_at`). |

## app/Repositories/Contracts/ + Eloquent/ (15 + 15 = 30 files)

One interface + one Eloquent implementation per aggregate:
`Tenant`, `Company`, `CompanySetting`, `Branch`, `Department`, `Role`,
`Permission`, `User`, `Task`, `Notification`, `Lead`, `LeadSource`,
`LeadStatus`, `LeadActivity`, plus `RepositoryInterface`/`BaseRepository`
(the shared base every other one extends — filtering/sorting/searching
via `spatie/laravel-query-builder`, wired centrally).

**Notes:** `RepositoryInterface`/`BaseRepository` are required by every
other repository — deleting either breaks the whole layer.

## app/Services/ (21 files)

| File | Purpose |
|---|---|
| `AuthService.php` | Login/OTP/logout orchestration, incl. the Super Admin login bootstrap. |
| `TokenService.php` | Refresh-token rotation-on-reuse. |
| `RegistrationService.php` | Atomic company-registration transaction — binds `TenantContext` mid-transaction (a real bug fix, see `TENANT_ISOLATION_REVIEW.md`). |
| `RoleProvisioningService.php` | Default role + permission grants at registration. |
| `CrmProvisioningService.php` | Default lead sources/statuses at registration — same role as `RoleProvisioningService` for CRM. |
| `PasswordPolicyService.php`, `PasswordResetService.php` | Password strength/history/reset logic. |
| `OtpService.php` | MFA code + ticket lifecycle. |
| `EmailVerificationService.php` | Verification email send/confirm. |
| `LoginRateLimiter.php` | Dual (email+tenant, IP) throttle. |
| `DeviceService.php` | Device fingerprinting for "known devices". |
| `ActivityLogService.php`, `AuditLogService.php` | The two logging backends `Auditable` writes to. |
| `RoleService.php` | Custom role CRUD + permission assignment (tenant_id-safe pivot writes). |
| `UserService.php` | Invite/update/status/branch-assignment/admin-reset-password. |
| `DashboardService.php` | Platform Admin dashboard aggregates + honest "not installed" widget shape. |
| `NotificationService.php` | Multi-channel dispatch with per-category preference resolution. |
| `TaskService.php` | Task create/update + assignment notification. |
| `LeadService.php` | Lead create/update/assign + timeline logging. |
| `CrmDashboardService.php` | CRM dashboard aggregates, ownership-scoped for Sales. |
| `SequenceService.php` | Generic atomic per-tenant sequence counter — used by Leads today, designed for reuse by any future numbered document. |

## app/Http/Controllers/ (35 files across Api/V1/{Admin,Auth,Crm,Platform} + base Controller.php)

Grouped by the `routes/api.php` prefix they're mounted under — see
`FEATURE_MATRIX.md` for the endpoint count per group.

**Note on `Controller.php`:** the shared base class. It gained the
`AuthorizesRequests` trait during CRM Sprint 1 specifically because
`LeadController` was the first controller to call `$this->authorize()`
— every controller depends on this file compiling correctly.

## app/Http/Requests/ (28 files) + app/Http/Resources/ (16 files)

One Form Request per mutating endpoint (Store/Update pairs kept
separate after a real bug — reusing a Store request for Update rejected
partial PATCH bodies missing required-on-create fields, caught by
`BranchDepartmentTest`), one Resource per model that's ever serialized.

## app/Http/Middleware/ (5 files)

| File | Purpose |
|---|---|
| `ResolveTenant.php` | Global — resolves tenant from subdomain/header, binds RLS session vars. |
| `BindAuthenticatedTenant.php` | Post-auth — verifies token-tenant match, rejects mismatches, falls back to the token owner's own tenant if none resolved. |
| `EnsureTenantIsActive.php` | Blocks suspended/cancelled tenants. |
| `CheckPermission.php` | The RBAC gate every `permission:module.action` route middleware resolves to. |
| `TrackRequestActivity.php` | Touches `UserDevice.last_seen_at`. |

## app/Policies/ (1 file)

`LeadPolicy.php` — the only Policy in the codebase, registered via
`Gate::policy()` in `AppServiceProvider::boot()`. Record-level ownership
check for Sales-role lead access, layered on top of route-level
permission middleware.

## app/Providers/ (2 files)

`AppServiceProvider.php` (binds `TenantContext` singleton, registers
`LeadPolicy`), `RepositoryServiceProvider.php` (every interface→
implementation binding — the single place that determines which
Eloquent class backs each repository interface).

## app/Multitenancy/, app/Notifications/, app/Mail/, app/Support/, app/Console/ (6 files)

`TenantContext.php` (request-scoped tenant holder — see
`PROJECT_STATUS.md`'s RLS note), `VerifyEmailNotification.php` /
`PasswordResetNotification.php` (Mailables via Laravel's Notification
system), `NotificationMail.php` (generic mailable for
`NotificationService`), `BrowserParser.php` (user-agent → human string
for the Activity Log), `ProvisionCrmDefaultsCommand.php` (backfill
console command — `php artisan crm:provision-defaults`).

---

## database/migrations/ (38 files)

Grouped by date prefix = sprint: `2025_01_01_*`/`2025_01_02_*` (23,
Foundation + hardening), `2025_02_01_*` (8, Platform Admin),
`2025_03_01_*` (7, CRM). Every migration has a corresponding `down()`.
**None have ever actually been run in this environment** — see
`PROJECT_STATUS.md`'s runtime-verification caveat.

## database/factories/ (12) + database/seeders/ (4)

One factory per model that needs test data (`Tenant`, `Company`,
`Branch`, `Department`, `Role`, `Permission`, `User`, `Task`,
`Notification`, `Lead`, `LeadSource`, `LeadStatus`). Seeders:
`PermissionSeeder` (reads `config/permissions.php` — the single source
of truth), `SuperAdminSeeder`, `DemoTenantSeeder` (local/testing only,
no-ops elsewhere — not production seed data), `DatabaseSeeder`
(orchestrates the three in order).

---

## routes/ (2 files)

`api.php` (212 lines, all 91 endpoints), `console.php` (one inspirational
Artisan command, framework default — **could be deleted** with zero
impact; it's the only file in this index that isn't load-bearing).

## config/ (4 files)

`permissions.php` (the permission catalog single source of truth —
**the most important config file in the project**: every module's RBAC
extension is "add a block here"), `tenancy.php`, `sanctum.php`,
`security.php`.

## bootstrap/ (2 files)

`app.php` (middleware pipeline + exception→JSON-envelope mapping —
required by literally every request), `providers.php` (the two service
providers).

## docker/, docker-compose.yml, resources/ (4 files)

Local-dev-only. `docker-compose.yml` + `docker/php/Dockerfile` +
`docker/nginx/default.conf` define app/nginx/postgres/redis/queue/
scheduler/mailhog. **No production deployment configuration exists
anywhere** — see `AUDIT_REPORT.md` Part 11. `resources/views/mail/
notification.blade.php` is the one Blade view in the project (an email
template, not a frontend page).

## tests/ (27 files)

See `AUDIT_REPORT.md` Part 8 for full coverage analysis — not repeated
here since it needs narrative, not a file-by-file purpose list.

## docs/ (files inside this repo)

`FOUNDATION.md`, `TENANT_ISOLATION_REVIEW.md`, `PLATFORM_ADMIN_MODULE.md`,
`CRM_MODULE.md` — all current, all accurate, all **should be kept as
official documentation.** See `AUDIT_REPORT.md` Part 4 for the
documentation review, including files that exist **outside** this repo
(prior chat outputs) that are now superseded and should be archived.

## Root level

**Missing, not present:** `README.md`. There is no entry-point document
explaining what this repository is, how to run it, or where to start
reading — a real gap for "professional enterprise repository"
organization. Not created as part of this audit (audit-only scope), but
this is the single highest-value 30-minute fix available. **No
`.git` directory exists either** — this project has never been placed
under version control in this environment.
