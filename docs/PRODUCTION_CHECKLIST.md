# SoudaCore ERP — Production Checklist

A concrete, actionable checklist for taking this application live for
real — distinct from `docs/PRODUCTION_READINESS.md` (which documents
*what was built* and *why*) and `docs/FINAL_REPORT.md` (which
documents *what was reviewed and verified*). This is the list an
operator actually runs through before flipping real traffic on.

Every item below is real and specific to this codebase — not a
generic checklist. Where something has never been executed in this
project's own development sandbox (a standing, honestly-disclosed
limitation — see `docs/FINAL_REPORT.md`), that's marked explicitly
rather than presented as done.

## Before you deploy anywhere

- [ ] `composer install` succeeds in your real target environment
      (this has never been possible in this project's own development
      sandbox — Packagist has returned HTTP 403 throughout every
      sprint; this is the first real opportunity to confirm it works)
- [ ] `php artisan test` passes — the real, first execution of this
      project's test suite (hundreds of tests across 66 files, all
      `php -l` syntax-checked but never run via `phpunit` until now)
- [ ] `.env` created from `.env.example` with every required value set
      for real (not left as a placeholder) — see
      `docs/DEPLOYMENT_GUIDE.md` Step 2 for the specific list
- [ ] `APP_KEY` generated (`php artisan key:generate`) — a real,
      unique key, never reused across environments
- [ ] `APP_ENV=production` and `APP_DEBUG=false` — confirm both; with
      `APP_DEBUG=true` in production, stack traces leak in error
      responses (verified this doesn't happen when `false` — see
      `bootstrap/app.php`'s exception handler)
- [ ] `DB_PASSWORD` is a real, unique secret — not the `secret`
      placeholder `docker-compose.yml` ships with for local dev
      convenience

## Database

- [ ] `php artisan migrate --force` run against your real production
      database — all 84 migrations, verified repeatedly against real
      PostgreSQL in this sandbox (`tools/db-verify/`), but this is
      their first run against a real *application* database (not the
      narrower `soudacore_verify` structure-only database this
      project's own tooling uses)
- [ ] Row-Level Security confirmed active on your real database too:
      `SELECT count(*) FROM pg_tables WHERE schemaname='public' AND
      rowsecurity;` should return 101 (the same count this project's
      own sandbox confirmed — see `docs/FINAL_REPORT.md`)
- [ ] A first real backup taken and verified restorable — follow
      `docs/BACKUP_RESTORE_GUIDE.md`'s manual restore steps against a
      throwaway database *before* relying on this in a real incident

## Security

- [ ] TLS terminated in front of the application (`docs/DEPLOYMENT_GUIDE.md`
      Step 5) — `Strict-Transport-Security` only gets set when
      `$request->secure()` is true, so confirm it's actually appearing
      on responses once TLS is live
- [ ] `trustProxies(at: '*')` (`bootstrap/app.php`) scoped down to your
      real proxy/load-balancer IP range — the wildcard is safe behind
      a single trusted proxy layer you control, not once you don't
      know exactly what's in front of the app
- [ ] Rate limiting confirmed working for real: attempt 11 rapid
      requests to `/api/v1/auth/login` and confirm the 11th returns
      429 (this exact behavior is covered by `SecurityHardeningTest`,
      never executed via `phpunit` until your first real test run)
- [ ] Secrets (`DB_PASSWORD`, `APP_KEY`, any `AI_PROVIDER` API keys,
      `ERROR_TRACKING_WEBHOOK_URL`) stored via your real secrets
      management practice, never committed — `.env` is git-ignored,
      confirmed, but that only protects against committing it, not
      against weak file permissions on the server (`chmod 600`)

## Monitoring & operations

- [ ] `GET /api/v1/health` wired into your real uptime monitor / load
      balancer health check — not Laravel's built-in `/up`, which only
      confirms the process booted, not that the database/cache/queue
      are actually reachable
- [ ] `ERROR_TRACKING_WEBHOOK_URL` configured if you want real error
      alerting beyond log files — verify a real test exception
      actually reaches your webhook endpoint once deployed
- [ ] The queue worker (`docker-compose.yml`'s `queue` service, or
      your own `queue:work` process) confirmed actually running — this
      matters more than it might look: `NotificationMail` and
      `ScheduledReportMail` were fixed this project's Production
      Readiness sprint specifically because they'd been silently
      sending synchronously despite the queue infrastructure existing;
      confirm for real that a queued email actually goes through the
      worker, not just that the worker process is alive
- [ ] The scheduler (`docker-compose.yml`'s `scheduler` service)
      confirmed running — `reports:process-scheduled` (hourly) and
      `backup:database` (daily 02:00) both depend on it; verify with
      `php artisan schedule:list` and, after 24 hours live, confirm a
      real backup file actually appeared in `storage/app/backups/`
- [ ] Off-host backup replication wired up — this project's own
      `backup:database` command does not do this itself (see
      `docs/BACKUP_RESTORE_GUIDE.md`); `config/filesystems.php`'s `s3`
      disk is ready to configure but nothing copies backups there
      automatically yet

## CI/CD

- [ ] `.github/workflows/ci.yml` triggered for real at least once —
      this has never happened from this project's development sandbox
      (no git remote exists here); confirm it actually installs
      dependencies, runs migrations against its Postgres service
      container, and runs the real test suite successfully
- [ ] `DEPLOY_HOST`/`DEPLOY_USER`/`DEPLOY_SSH_KEY` configured as real
      GitHub Environment secrets if you use the workflow's `deploy`
      job — never committed anywhere

## Frontend / integration

- [ ] The console (`resources/views/app/console.blade.php`) actually
      loads in a real browser against your real deployed API — this
      has never been rendered against a live server in this project's
      development sandbox (confirmed repeatedly across every sprint);
      this is the first real opportunity to catch any integration
      issue between the frontend's `fetch()` calls and the real API
      responses
- [ ] A real end-to-end smoke test by hand: register a tenant, log in,
      create a record in at least one module per major area (CRM,
      Sales, Inventory, Accounting, HR, Reports, AI), confirm each
      works against the real deployed system

## After launch

- [ ] Confirm real tenant data isolation with two real tenants — not
      just this project's own automated tenant-isolation tests (which
      have never executed via `phpunit`), but a real manual check:
      log in as User A (Tenant 1), confirm you cannot see or reach any
      of Tenant 2's data through any screen or direct API call
- [ ] Monitor `php artisan queue:failed` for the first few real days —
      any job landing there is worth investigating immediately while
      the system is still new
- [ ] Review real error-tracking output (webhook or logs) daily for
      the first week

## What this checklist cannot promise

Every item above that says "this has never been executed in this
project's development sandbox" is real and unavoidable — this
project's own sandbox has never had internet access to run `composer
install`, meaning nothing here has ever booted as a live application,
run a real test via `phpunit`, or served a real HTTP request. Every
piece of code was written correctly, reviewed carefully, and verified
everywhere it *could* be verified without a live boot (syntax, real
PostgreSQL migrations and RLS, YAML/bash validity, generated file
formats checked against real external tools). This checklist is the
bridge between that and genuine production confidence — running
through it for real, in a real environment, is not optional busywork;
it's the actual first real-world test this project gets.
