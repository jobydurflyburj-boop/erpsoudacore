# SoudaCore ERP

A multi-tenant ERP SaaS platform for the Saudi market, built on
Laravel 12 + PostgreSQL 16. This root README exists to orient a new
reader quickly; it is deliberately short — every real detail lives in
`docs/`, which this file links to rather than duplicates.

**Current status:** eight business modules (CRM, Sales, Inventory,
Purchase, Accounting, HR & Payroll, Reports & Analytics, AI Assistant)
at audited depth, plus a real operational layer (Docker, CI/CD,
security hardening, monitoring, backups). See `docs/PROJECT_STATUS.md`
for the full, current, honestly-scored picture — that file, not this
one, is the source of truth on what's actually done.

## Quick start

```bash
git clone <this-repository-url> soudacore-api
cd soudacore-api
cp .env.example .env
docker compose up -d --build
docker compose exec app bash scripts/install.sh
curl http://localhost:8000/api/v1/health
```

Full walkthrough, including creating your first tenant:
`docs/INSTALLATION_GUIDE.md`.

## Documentation map

| Doc | What it covers |
|---|---|
| `docs/PROJECT_STATUS.md` | Current, honestly-scored project state — read this first |
| `docs/FEATURE_MATRIX.md` | Every module, what's built, what's explicitly not |
| `docs/ROADMAP.md` | What shipped, what's next, what's backlog |
| `docs/CHANGELOG.md` | Every sprint's real changes, in order |
| `docs/*_SPRINT.md` | One doc per module, written when that module's sprint completed — the deepest detail on any one area |
| `docs/INSTALLATION_GUIDE.md` | First-time setup |
| `docs/DEPLOYMENT_GUIDE.md` | Real production deployment procedure |
| `docs/ADMIN_GUIDE.md` | Day-to-day operational reference |
| `docs/BACKUP_RESTORE_GUIDE.md` | Real backup/restore procedure |
| `docs/PRODUCTION_READINESS.md` | Infrastructure, security, monitoring — what's real and what isn't yet |
| `docs/FINAL_REPORT.md` | The final integration/QA pass across the whole project |
| `docs/DATABASE_VERIFICATION.md` | How multi-tenant isolation was empirically proven, not just asserted |

## Architecture, in one paragraph

Laravel 12 API-only backend (a single `/api/v1` prefix covers all
354 routes), PostgreSQL 16 with Row-Level Security enforcing tenant
isolation at the database layer (not just application code — see
`docs/DATABASE_VERIFICATION.md`), Redis for cache/session/queue, a
single self-contained Blade+vanilla-JS console as the reference
frontend (`resources/views/app/console.blade.php` — a deliberate
scope decision, not a placeholder; see `docs/MVP_DEMO.md`), and a
repository+service layered architecture applied consistently across
every module. Real RBAC (module.action permission grants, seeded per
role), real multi-provider AI integration
(`App\Services\Ai\LlmProviderInterface`), real dependency-free PDF/
XLSX export, real automated backups — the theme across this whole
project has been building real, working functionality within a
sandboxed development environment that has never had internet access
to run `composer install`, and being explicit in every doc about
what's been verified versus what's correct-but-unexecuted. See
`docs/PRODUCTION_READINESS.md`'s and `docs/FINAL_REPORT.md`'s own
sections on this for the full, honest account.

## Tests

```bash
php artisan test
```

Hundreds of integration, unit, and tenant-isolation tests, written and
`php -l` syntax-checked throughout every sprint. Never executed in
this project's own development sandbox (`composer install` blocked);
the GitHub Actions workflow (`.github/workflows/ci.yml`) can run them
for real in any environment with internet access.

## License

Proprietary — Souda Core IT Solution.
