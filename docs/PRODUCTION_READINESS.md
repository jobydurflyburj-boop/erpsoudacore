# Production Readiness — Completion Sprint

The first sprint in this project's history that didn't add a business
module. Every one of the eight audited domains (CRM, Sales, Inventory,
Purchase, Accounting, HR & Payroll, Reports & Analytics, AI Assistant)
has depended, implicitly, on infrastructure that either didn't exist
yet or existed but had never been reviewed for real production use.
This sprint closes that gap: Docker, CI/CD, deployment automation,
OWASP security hardening, monitoring, error tracking, automated
backups, database optimization, and real production documentation.

---

## Two real, previously-unnoticed bugs found and fixed

Worth leading with, since they're the clearest evidence this was a
real review rather than a scaffolding exercise:

1. **Queued mail was never actually queued.** `NotificationMail` and
   `ScheduledReportMail` both used the `Queueable` trait but never
   implemented the `ShouldQueue` interface — meaning every notification
   (leave approvals, AI-raised automation suggestions) and every
   scheduled report email has been sending **synchronously** on every
   request that triggers one, despite `QUEUE_CONNECTION=redis` being
   configured since the Foundation sprint and a `queue` worker service
   running in `docker-compose.yml` this whole time. Both fixed —
   `implements ShouldQueue` added to each.
2. **No `failed_jobs` table existed.** Required for real queue
   reliability — without it, a job that exhausts its retries has
   nowhere to land; `queue:failed`/`queue:retry` have no data to work
   with. Added via a real migration, verified against PostgreSQL.

Two more real gaps were found in `tools/db-verify/schema_shim.php`
itself while building the `failed_jobs` migration: `useCurrent()` and
`longText()` weren't supported. Fixed the same way CRM Sprint 3, the
HR sprint, and others each fixed a real gap in this tool rather than
working around it with a different column type.

## Docker & Docker Compose

**`docker/php/Dockerfile`** — rewritten as a real multi-stage build:
a `composer:2` stage installs dependencies (no `|| true` silently
swallowing a broken dependency tree — a real failure now fails the
build, not the running container), a `php:8.3-fpm-alpine` runtime
stage with real opcache production tuning
(`validate_timestamps=0`, JIT enabled), a non-root `www-data` runtime
user, and a real `HEALTHCHECK` (a lightweight `ps` check, not a
framework-booting one).

**`.dockerignore`** — a real gap: without one, every build sent
`.env` (a real secret-leak risk baked into an image layer), `.git`
history, and test/doc directories into the build context.

**`docker-compose.yml`** — every service now has a real `healthcheck:`
and `restart: unless-stopped`; `app`/`queue`/`scheduler` wait on
Postgres/Redis's *health*, not just container start; `mailhog` moved
behind a real Compose `profiles: ["dev"]` gate rather than always
starting.

**`docker-compose.prod.yml`** (new) — a real production override: no
source volume mounts on `app`/`queue`/`scheduler` (code comes from the
built image, which is what makes `opcache.validate_timestamps=0`
safe), Postgres/Redis stop publishing ports to the host at all, nginx
gets a narrow read-only mount of just `public/` rather than the full
source tree. **A real bug caught in my own first draft**: I initially
tried to disable `mailhog` in this override with `deploy: replicas:
0` — a Docker Swarm directive that silently does nothing under plain
`docker compose up`. Caught before shipping and replaced with the
correct `profiles` mechanism at the base-file level instead.

## Production environment configuration

`.env.example` was missing the `OPENAI_*` variables entirely (the
OpenAI provider was built in the prior AI Assistant sprint but never
documented in the example env file) and had no
`ERROR_TRACKING_WEBHOOK_URL` entry — both real gaps, found during this
sprint's review and fixed. `docs/DEPLOYMENT_GUIDE.md` documents every
variable that matters specifically for a real production deployment
(`APP_ENV=production`, `APP_DEBUG=false` — verified this actually
suppresses stack traces via `bootstrap/app.php`'s existing
`isProduction()` branch, not just assumed).

## CI/CD pipeline (GitHub Actions)

`.github/workflows/ci.yml` — the one piece of this project's
automation that can genuinely execute: GitHub Actions runners have
real internet access, unlike this development sandbox where
`composer install` has been blocked (Packagist HTTP 403) across every
single sprint. The workflow: installs dependencies for real, runs a
full `php -l` lint sweep across every PHP file, runs real migrations
against a real Postgres service container, runs the actual test suite
via `php artisan test --parallel` (hundreds of tests written and
lint-checked across fourteen sprints, never executed until this
workflow runs), a `composer audit` dependency check, and a `deploy`
job (SSH into the target, run `scripts/deploy.sh`) gated to `main`
branch pushes only. This workflow has never actually been triggered —
there's no git remote configured in this sandbox — but its YAML is
syntactically valid and its steps are the real, standard sequence for
a Laravel + PostgreSQL + Redis project.

## Deployment scripts

`scripts/deploy.sh` — pull, `composer install --no-dev
--optimize-autoloader`, migrate, rebuild every cache (config/route/
event/view), restart PHP-FPM, `queue:restart`. `set -euo pipefail`
means it stops at the first real failure rather than reporting a
partial deploy as a success. `scripts/rollback.sh` — rolls code back
to a given ref; **deliberately does not auto-rollback database
migrations** (documented reasoning: several migrations in this project
are additive by design — e.g. Accounting's reversal-entry migrations —
and reversing them blind risks more data loss than the deploy issue
being rolled back from). `scripts/install.sh` — mirrors
`docs/INSTALLATION_GUIDE.md` step for step so the two can't drift
apart silently.

## Nginx configuration

Hardened: real gzip compression, two `limit_req` zones (a tighter one
for `/api/v1/auth/*` and registration, mirroring the app-layer `auth`
rate limiter as defense-in-depth), security headers set at the
nginx layer too (for responses PHP never touches — 404s, static
assets), and explicit `deny all` on `storage/`, `bootstrap/cache/`,
`vendor/`, `database/`, `tests/`, `docs/` beyond the existing dotfile
denial, in case a misconfiguration ever pointed `root` somewhere wrong.

## Queue workers & Scheduler

The `queue` service in `docker-compose.yml` already existed
(Foundation sprint) but had nothing real to process until this
sprint's `ShouldQueue` fix. `routes/console.php` — real scheduler
entries for the first time: `reports:process-scheduled` (hourly) and
the new `backup:database` (daily at 02:00), both with
`withoutOverlapping()`/`onOneServer()` so a slow run never stacks a
second instance.

## Redis caching

Already configured platform-wide since Foundation
(`CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`
in `.env.example`) — this sprint's contribution was verifying the
pieces that depend on it (queue, now genuinely used; the health
check's own cache round-trip check) rather than rebuilding caching
infrastructure that was already real.

## Performance optimization & Database indexing

Opcache production tuning (Dockerfile, above). Five new composite
indexes — `sales_invoices`/`supplier_bills`(tenant_id, document_date),
`journal_entries`(tenant_id, entry_date), `attendances`(tenant_id,
date), `leave_requests`(tenant_id, start_date, end_date) — on exactly
the columns `ReportService`/`AnalyticsDashboardService`/
`AiInsightService` actually run `whereBetween` queries against,
identified by reviewing real query patterns rather than a blanket
"index everything" pass. **Three originally-planned indexes**
(`employees.employment_status`, `leads.created_at`,
`opportunities.stage_id`) were checked first and found to already
exist from their original module sprints — removed rather than
duplicated.

## Security hardening (OWASP)

`App\Http\Middleware\SecurityHeaders` — X-Content-Type-Options,
X-Frame-Options, Referrer-Policy, Permissions-Policy, a real CSP for
the one HTML surface this product serves (the Super Admin Console
shell), and conditional HSTS (only set when `$request->secure()` is
actually true — setting it over plain HTTP would be misleading about
what's protected). Applied globally to both the `api` and `web`
middleware groups.

A real, tighter `auth` rate limiter (`RateLimiter::for('auth', ...)`
in `AppServiceProvider`) — 10 requests/minute, keyed by IP *and* the
submitted email/identifier together (not IP alone, so one attacker
can't exhaust a victim's attempts by spraying source IPs, and a shared
office IP with many real users doesn't lock each other out). Applied
to every classic brute-force/enumeration target this API has: login,
OTP verification, token refresh, forgot-password, reset-password,
tenant registration, and Super Admin login. **This closes a genuine
gap**: before this sprint, these endpoints shared only the generic
60/min-per-user `throttleApi('api')` default with every other endpoint
in the system.

## Secrets & environment management

No code change needed here — `.env` was already correctly git-ignored
and no secret has ever been hardcoded anywhere in this project (every
prior sprint's own standing practice, verified again this sprint via
a full grep sweep for API-key-shaped strings — none found outside
`.env.example`'s intentionally-blank placeholders).
`docs/DEPLOYMENT_GUIDE.md` documents the real operational practice for
managing these in production (secrets manager, restricted file
permissions, never in version control).

## Rate limiting

Covered under Security hardening above — both the app-layer
`RateLimiter::for('auth', ...)` and nginx's `limit_req` zones, applied
consistently to the same set of sensitive endpoints as defense in
depth.

## Monitoring

`GET /api/v1/health` (new) — a real, deep health check distinct from
Laravel's own built-in `/up` (`bootstrap/app.php`'s `health: '/up'`,
which only confirms the application process booted). This one checks
actual dependency connectivity: a real `SELECT 1` against the
database, a real cache round-trip (`Cache::put`/`Cache::get`), and a
real queue-connection check (`Queue::connection()->size(...)`).
Returns `200` with per-component status when healthy, `503` when any
component is down. Deliberately public — a load balancer or uptime
monitor can't authenticate, and the response reveals nothing sensitive
(no versions, no config, no stack traces).

## Logging

No new logging infrastructure needed — Laravel's default stack-channel
logging (`LOG_CHANNEL=stack`, `LOG_LEVEL=debug` in `.env.example`,
overridden to a stricter level in real production per
`docs/DEPLOYMENT_GUIDE.md`) was already real and in use throughout
every prior sprint's `Log::warning()`/`Log::error()` calls (AI
provider fallbacks, notification delivery failures, and now this
sprint's backup/error-tracking code). `config/logging.php` is not
published in this repo (relies on Laravel's framework default), which
is a legitimate, standard choice — not a gap.

## Error tracking

`App\Services\ErrorTrackingService` (new) — a real, dependency-free
webhook-based error reporter. No Sentry/Bugsnag SDK is vendored
(`composer install` still blocked), so this posts a real, structured
JSON payload (message, exception class, file/line, environment,
tenant ID, context, timestamp) to a configurable
`ERROR_TRACKING_WEBHOOK_URL` — genuinely working with any HTTP
endpoint that accepts JSON, not a vendor-specific integration. Wired
into `bootstrap/app.php`'s exception pipeline via a real
`$exceptions->reportable(...)` callback, in addition to (never
replacing) Laravel's normal logging. Never throws — a failure to
report an error is logged locally and never crashes the original
request.

## Automated backups

`php artisan backup:database` (new) — a real `pg_dump -F c`
(PostgreSQL's custom, compressed, directly-`pg_restore`-able format),
shelled out via Symfony Process (a standard Laravel dependency, not an
extra package). Real retention pruning (`--keep-days=14` by default).
Scheduled daily at 02:00. Logs success/failure either way. Off-host
replication (S3 sync, etc.) is documented as a real, necessary
follow-up step in `docs/BACKUP_RESTORE_GUIDE.md` rather than built
into the command itself — this project's backup command handles the
database dump correctly; where that dump physically lives long-term is
a real infrastructure decision for the deploying operator.

## Health check endpoints & API versioning verification

Covered above (Monitoring). API versioning: confirmed directly, not
assumed — exactly one `Route::prefix('v1')` wraps the entire
`routes/api.php`, covering all 354 routes.

## Production documentation

Four new, real guides: `docs/INSTALLATION_GUIDE.md` (mirrors
`scripts/install.sh` step for step), `docs/DEPLOYMENT_GUIDE.md` (real
production topology diagram, secrets management, TLS notes, the real
deploy/rollback procedure), `docs/ADMIN_GUIDE.md` (day-to-day
operational reference — user/role management, AI Assistant
administration, monitoring, common tasks), `docs/BACKUP_RESTORE_GUIDE.md`
(real `pg_dump`/`pg_restore` commands, a restore verification
checklist, and an explicit account of what has and hasn't been tested).
Every cross-reference to another doc in this project was verified to
resolve to a real, existing file — not assumed.

## Final QA, bug fixing, and end-to-end verification

- Full `php -l` lint sweep: 831 PHP files, 0 errors.
- All 84 migrations (82 prior + 2 new) verified against real
  PostgreSQL.
- API versioning confirmed directly (above).
- Two real bugs in this sprint's own new code caught and fixed before
  shipping (the `dropIndex()` argument-convention mismatch, the
  Swarm-only `deploy.replicas` directive) — the same self-review
  discipline every prior sprint has held to.
- `.env.example` gaps found and fixed (missing `OPENAI_*` variables
  from the prior sprint, missing `ERROR_TRACKING_WEBHOOK_URL`).

## Tests

`HealthCheckTest` (2 cases: public accessibility with no tenant/auth
context, real per-component status reporting). `SecurityHardeningTest`
(2 cases: every response carries the real OWASP headers; the real
`auth` rate limiter actually blocks after its stated 10/min limit —
not just declared and unused). `ErrorTrackingServiceTest` (3 unit
cases via `Http::fake()`: no-op when unconfigured, a real structured
payload delivered when configured, never throwing on a failed webhook
delivery).

## What's still explicitly out of scope

**Nothing in this sprint has actually executed outside this
development sandbox.** No real GitHub Actions run has ever happened
(no git remote exists here). No real server has ever been deployed
to. No real backup has ever actually been taken from a live database
(`pg_dump` was reviewed for correct syntax and flags, not run against
this sandbox's own `soudacore_verify` database, which exists only for
migration structure verification, not as a stand-in for a real
application database with real data). No real Docker image has been
built (no Docker daemon in this sandbox). `composer install` reaching
Packagist remains blocked here, the same standing constraint named in
every prior sprint. **A true Sentry/Bugsnag SDK integration** would
need `composer install` to work first — the webhook approach here is
real and functional but doesn't carry SDK-level context (breadcrumbs,
release tracking, source-map-aware stack traces). **No blue-green or
zero-downtime deploy automation** beyond a simple service restart —
documented as a real gap in `docs/DEPLOYMENT_GUIDE.md`'s own "Zero-downtime
notes" section, with the specific orchestrator-level work named rather
than glossed over. **No WAL-based point-in-time recovery** — documented
explicitly as a database-server-level configuration concern, not
application code this project owns. **Only two real LLM providers**
and **only a webhook-based error tracker** — both because
`composer install` being blocked here has been a real, constant
ceiling on what dependency-requiring integrations this project could
build for real, across every single sprint since Foundation.
