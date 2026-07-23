# SoudaCore ERP — Admin Guide

A day-to-day operational reference for whoever administers a live
SoudaCore deployment — both the platform level (Super Admin, managing
tenants) and the tenant level (Company Owner/Admin, managing one
business's own data). This is not a feature walkthrough (each module's
own sprint doc covers what it does); it's the "how do I actually run
this thing" reference.

## Two distinct admin surfaces

**Super Admin** (`/api/v1/admin/*`, separate login at
`/api/v1/admin/auth/login`) — platform-level: create/suspend/reactivate
tenants, view platform-wide metrics, impersonate for support (if
enabled — see `docs/SUPER_ADMIN_CONSOLE.md`). A Super Admin identity
is entirely separate from any tenant's own users.

**Tenant Admin** (Company Owner / Admin role, inside a tenant) —
everything under `/api/v1/v1/*` scoped to that one tenant: users,
roles, all business modules, AI Settings, Reports.

## Managing users and roles

Default roles are seeded per-tenant at registration
(`RoleProvisioningService` — see `docs/FOUNDATION.md`): Company Owner,
Admin, Manager, Sales, Accountant, HR, Inventory, Cashier, Employee.
Every permission grant for every module is defined in
`config/permissions.php` and `app/Services/RoleProvisioningService.php`
— to see exactly what a role can do, read the grant matrix there
directly rather than guessing from the UI. Custom roles/permission
edits are managed via the standard Users & Roles screen in the
console (`/api/v1/admin/roles`).

## AI Assistant administration

Real, tenant-level configuration — not an environment variable a
tenant admin needs platform access to change:

- **AI Settings** (`GET`/`PATCH /api/v1/ai/settings`) — master on/off,
  Insights/Notifications/Automation Suggestions on/off individually,
  and `provider_override` (choose Anthropic or OpenAI specifically,
  if the platform has real credentials configured for both — see
  `docs/AI_ASSISTANT_SPRINT.md`).
- **AI Prompt Templates** (`GET`/`PUT /api/v1/ai/prompt-templates`) —
  customize the system prompt for chat or any of the five insight
  types; reset to the built-in default any time.
- **AI Activity Log** (`GET /api/v1/ai/activity-logs`) — a real,
  queryable audit trail of every AI feature invocation for this
  tenant.

Platform-level AI provider credentials (`ANTHROPIC_API_KEY`,
`OPENAI_API_KEY`) are set once via environment variables — see
`docs/DEPLOYMENT_GUIDE.md`'s secrets section — not per-tenant.

## Scheduled Reports administration

A tenant admin builds a Custom Report
(`POST /api/v1/reports/custom-reports`), then schedules it
(`POST /api/v1/reports/scheduled-reports`) for real recurring email
delivery. The platform-level scheduler (`routes/console.php`, running
`reports:process-scheduled` hourly) is what actually sends these — a
tenant never needs shell/cron access themselves.

## Monitoring the deployment

- **`GET /api/v1/health`** — the real, deep health check (database,
  cache, queue connectivity) — see `docs/PRODUCTION_READINESS.md`.
  Point your uptime monitor here, not just Laravel's built-in `/up`
  (which only confirms the app process is alive, not its dependencies).
- **AI Activity Log, per tenant** — for AI-specific troubleshooting.
- **Application logs** — `storage/logs/laravel.log` (or wherever
  `LOG_CHANNEL` is configured to write — see
  `docs/PRODUCTION_READINESS.md`'s Logging section). Every unhandled
  exception is both logged here AND, if `ERROR_TRACKING_WEBHOOK_URL`
  is configured, delivered to your real external error tracker.
- **Queue health** — `php artisan queue:failed` lists any job that
  exhausted its retries (real, since `failed_jobs` table now exists —
  see this sprint's fix). `php artisan queue:retry all` retries every
  failed job.

## Backups

Automated daily (`routes/console.php`'s `backup:database` schedule
entry) — see `docs/BACKUP_RESTORE_GUIDE.md` for the full backup and
restore procedure, retention policy, and disaster-recovery steps.

## Common administrative tasks

| Task | How |
|---|---|
| Suspend a tenant (e.g. non-payment) | Super Admin console, or `POST /api/v1/admin/tenants/{id}/suspend` |
| Reactivate a tenant | `POST /api/v1/admin/tenants/{id}/reactivate` |
| Backfill a new default (e.g. a new Chart of Accounts entry added in a later sprint) for a tenant that registered before it existed | The relevant `*:provision-defaults` console command — e.g. `php artisan hr:provision-defaults {tenant-id}`, `php artisan accounting:provision-defaults {tenant-id}` — every provisioning service across every sprint has one, all idempotent and safe to re-run |
| Force a scheduled report to send immediately | `POST /api/v1/reports/scheduled-reports/{id}/run-now` |
| Reprocess failed queue jobs | `php artisan queue:retry all` (or `php artisan queue:retry {id}` for one) |
| Rotate a compromised API key/secret | Update the env var, `php artisan config:cache`, restart PHP-FPM (`scripts/deploy.sh` does the restart step) |
| Check what's actually scheduled | `php artisan schedule:list` |

## What this guide does not cover

Feature-level "how do I use module X" documentation — each module's
own sprint doc (`docs/*_SPRINT.md`) documents what it does and its
explicit "still out of scope" boundaries. This guide is operational
only: running, monitoring, and administering the deployed system.
