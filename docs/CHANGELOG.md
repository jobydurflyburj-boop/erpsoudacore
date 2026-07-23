# SoudaCore ERP — Changelog

Version labels here are a documentation convenience for tracking sprint
history — there is no git repository or tag in this environment, so
these numbers exist only in this file. Format loosely follows
[Keep a Changelog]. Each entry lists what shipped in that sprint, not a
commit-by-commit history.

---

## [2.1.0] — Critical Laravel Skeleton Repair

Triggered by a direct report that `artisan` was missing. Verified
directly rather than assuming anything else was fine, and found the
gap was far larger. A minor version bump, not a patch — this pass
fixed something more fundamental than verification or missing-piece
generation: **the application could not have booted at all before
this pass**, regardless of how correct any business module's code
was. Full detail: `docs/FINAL_REPORT.md`.

### Fixed — the real Laravel framework skeleton, genuinely missing entirely
- `artisan` — the real Laravel 12 CLI entry point.
- `public/index.php`, `public/.htaccess`, `public/robots.txt` — the
  real front controller and web-server config; without `index.php`,
  there was no HTTP entry point into this application at all.
- The entire `storage/` directory tree (`app/private`, `app/public`,
  `app/backups`, `framework/{cache/data,sessions,testing,views}`,
  `logs`) with the real Laravel-standard `.gitignore` placeholder in
  each subdirectory.
- `bootstrap/cache/` (with its own `.gitignore`).
- `config/app.php` — name/env/debug/url/timezone/locale/**encryption
  key**. Without this, the Encrypter that every signed URL in this
  project (including the email-verification route) depends on had no
  real key configured anywhere.
- `config/database.php` — **the single most critical gap of all**.
  Without this, Laravel had no real `pgsql` connection defined
  anywhere its own database layer could read, despite every
  migration, every model, and every RLS policy verified throughout
  every sprint depending on one.
- `config/auth.php`, `config/session.php`, `config/mail.php`,
  `config/cors.php` — each genuinely missing, each verified against
  real, already-correct env vars in `.env.example` before being
  written.

### Confirmed NOT missing, correctly
- `package.json`/`vite.config.js` — a direct search found zero
  `@vite` usage anywhere in `resources/`, consistent with this
  project's documented zero-build-step frontend decision
  (`docs/MVP_DEMO.md`).
- `config/broadcasting.php` — zero `ShouldBroadcast`/`Broadcast::`
  usage anywhere in `app/`.

### Why this went undetected across every prior sprint
`tools/db-verify/`'s own migration-verification tool bypasses
Laravel's bootstrap entirely by design — a raw PDO connection, not a
real `DB::` call. Every "migrations verified" claim across every prior
sprint was real and accurate for what it tested, but that tool never
exercised, and could never have caught, the fact that Laravel itself
had no real way to connect to that same database outside it.

### Verified
- Every new config file cross-checked against real `config('...')`
  calls already present in the application code
  (`ErrorTrackingService`, `BackupDatabaseCommand`) to confirm the
  exact keys those calls expect are the exact keys now provided.
- Full `php -l` lint sweep: 843 PHP files, 0 errors.
- All 84 migrations re-verified against real PostgreSQL, unaffected by
  any of the above.

### Explicitly still true, unchanged
`composer install` remains blocked in this sandbox. This repair makes
the application *able* to boot once dependencies install somewhere
with real internet access — it does not itself constitute that first
real boot, which still hasn't happened anywhere.

---

## [2.0.2] — Final Production Validation

A 20-item codebase review checklist plus generation of every
genuinely missing piece of production configuration and
documentation. No new business features, per this pass's own explicit
scope. Full detail: `docs/FINAL_REPORT.md` (updated in place to cover
this pass).

### Verified (scripted against the real files, not sampled)
- Zero broken model relationships — 238 relationship methods across
  102 models, each checked against the actual model class files.
- Zero namespace mismatches — 641 files under `app/`, each checked
  against its real directory path.
- Zero unresolved `App\`-namespaced imports — 641 files checked. This
  check's own first version had a real bug (didn't handle `abstract
  class`, producing 99 false positives on the base `Controller`
  class) — found, fixed, and re-run clean before being trusted.
- Confirmed this project has no React codebase — the frontend is a
  deliberately-scoped Blade+vanilla-JS console (`docs/MVP_DEMO.md`).
  Answered honestly rather than fabricating a React-page review.
- Re-confirmed zero tenant-scoped tables missing RLS (101 tables), the
  real `auth` rate limiter's coverage of every sensitive endpoint, and
  that the three real ownership-based Policies (`LeadPolicy`,
  `CustomerPolicy`, `OpportunityPolicy`) coexisting with the
  `permission:module.action` middleware pattern is the original,
  intentional architecture.
- 65 repository interface-to-binding pairs reconfirmed correct (the
  66th interface file is the base `RepositoryInterface`, never bound
  directly, by design).
- Every controller under `app/Http/Controllers/Api` confirmed to
  extend the base `Controller` class and to be referenced by a real
  route.

### Added
- `config/queue.php`, `config/cache.php`, `config/filesystems.php`,
  `config/logging.php` — all four were missing entirely, running on
  Laravel's unpublished internal defaults despite real code depending
  on them since Foundation. Now published with real settings: a
  `stderr` log channel for this project's containerized deployment, an
  `s3` filesystem disk ready for the off-host backup replication
  `docs/BACKUP_RESTORE_GUIDE.md` already named as necessary, and
  `failed_jobs` wired as the queue's real failure-recovery path.
- Matching `.env.example` additions: `FILESYSTEM_DISK`,
  `LOG_DAILY_DAYS`, an optional `AWS_*` block for the new `s3` disk.
- `docs/API_DOCUMENTATION.md` — auth flow, response/error envelope,
  filtering conventions, rate limiting, a verified module→path→
  permission→sprint-doc reference table. Honest that a full generated
  OpenAPI spec remains open.
- `docs/USER_GUIDE.md` — real end-user workflows (lead to paid
  invoice, purchase order to paid bill, leave requests, stock checks,
  AI Assistant usage, running/scheduling reports), distinct from the
  ops-focused Admin Guide.
- `docs/PRODUCTION_CHECKLIST.md` — a concrete, itemized pre-launch
  checklist, distinct from `PRODUCTION_READINESS.md` (what was built)
  and `FINAL_REPORT.md` (what was reviewed).

### Changed
- `docs/DEPLOYMENT_GUIDE.md` — new Step 3 covering the four
  newly-published config files; Steps 3–5 renumbered to 4–6, verified
  no stale internal cross-references remain.

### Verified (re-run)
- All 84 migrations run cleanly against real PostgreSQL.

### Explicitly still out of scope
A generated, machine-readable OpenAPI/Swagger spec for all 354
endpoints. `ramsey/uuid` and `pragmarx/google2fa-laravel` remain
unused/dead dependencies (a pre-existing, already-tracked backlog
item, deliberately not touched this pass). `composer install` reaching
Packagist remains blocked in this sandbox; no test has ever executed
via `phpunit`; no Docker image has ever been built.

---

## [2.0.1] — Final Report / Closing QA Pass

Not a new module — a closing Final Code Review, Security Audit,
Performance Optimization check, Bug Fix pass, and Documentation pass
across the whole project. Accounting, HR & Payroll, Reports &
Analytics, AI Assistant, and Production Readiness were all already
complete and were deliberately not rebuilt. Full detail:
`docs/FINAL_REPORT.md`.

### Verified (empirically, not assumed)
- Zero tenant-scoped tables missing Row-Level Security — confirmed via
  a direct query against a real, freshly-migrated PostgreSQL database
  (101 tables carry RLS).
- Zero duplicate database table creations across all 84 migrations (99
  unique tables).
- Zero duplicate JavaScript function names in `console.blade.php` (107
  functions) and zero duplicate keys in its `ROUTES` object (58 keys).
- Zero hardcoded secrets anywhere in `app/` or `config/`.
- 14 apparent route method+path "duplicates" investigated by hand and
  confirmed to be false positives — distinct routes under different
  `Route::prefix()` groups (e.g. four separate real `/dashboard`
  endpoints under `/crm`, `/sales`, `/purchase`, `/hr`).
- The repository-interface-to-binding count (66 files vs. 65 bindings)
  confirmed correct, not a gap — the 66th file is the base interface
  every concrete one extends.
- Full `php -l` lint sweep: 831 PHP files, 0 errors.
- All 84 migrations re-verified against real PostgreSQL as the final
  step.
- A single `/v1` prefix confirmed still wrapping all 354 routes.

### Added
- Root-level `README.md` — closes half of a backlog item named across
  many prior sprints (`ROADMAP.md`'s Backlog); a generated OpenAPI
  spec for the 354 endpoints remains explicitly open.

### Fixed
- `docs/FINAL_REPORT.md` — replaced a stale, early-project-era version
  (264 files/38 migrations/91 endpoints, a Foundation/MVP-demo-era
  snapshot) with a current, accurate one.
- One real bug, caught during this pass's own doc-editing: a
  `str_replace` on `PROJECT_STATUS.md` momentarily left a duplicated
  "Current phase" header with an orphaned sentence fragment between
  the two copies. Caught immediately by this project's own standing
  practice of grepping section headers before and after every edit,
  fixed in the same turn, and the report's own "no bugs found" draft
  claim was corrected afterward to disclose it honestly rather than
  left overstated.

### Explicitly still out of scope
Everything named as out of scope in every prior sprint's own doc
remains out of scope — this pass verified, it did not expand, the
project's real capabilities. `composer install` reaching Packagist
remains blocked in this sandbox; no test has ever executed via
`phpunit`; no Docker image has ever been built; a generated OpenAPI
spec does not yet exist.

---

## [2.0.0] — Production Readiness

The first sprint not adding a business module — closing the
operational gap every one of the eight audited business modules has
depended on implicitly since it shipped. Major version bump reflects
a real category change: every module in the original MVP demo scope
is at audited depth *and* the operational layer required to actually
run this in production is now real. Full detail:
`docs/PRODUCTION_READINESS.md`.

### Fixed — two real, previously-unnoticed production bugs
`NotificationMail` and `ScheduledReportMail` were declared with the
`Queueable` trait but never implemented `ShouldQueue` — every
notification and scheduled-report email has been sending
*synchronously* despite `QUEUE_CONNECTION=redis` being configured
since the Foundation sprint. Both fixed. The `failed_jobs` table,
required for real queue reliability (tracking/retrying jobs that
exhaust their retries), never existed — added.

### Added — Docker & Docker Compose
A hardened multi-stage `Dockerfile` (non-root runtime user, opcache
production tuning, a real `HEALTHCHECK`, fixing a prior `composer
install ... || true` that silently swallowed install failures),
`.dockerignore` (a real gap — `.env`/`.git` were being sent into every
build context), health-checked and restart-policy'd services in
`docker-compose.yml`, and a real `docker-compose.prod.yml` override
(no source volume mounts in production; Postgres/Redis stop
publishing ports to the host; `mailhog` gated behind a real Compose
`profiles` mechanism — replacing a first-draft `deploy.replicas: 0`
that silently does nothing outside Swarm mode, caught and fixed
before shipping).

### Added — CI/CD pipeline (GitHub Actions)
A real workflow (`.github/workflows/ci.yml`): dependency install, a
full `php -l` lint sweep, real migrations against a real Postgres
service container, the real test suite via `php artisan test
--parallel`, a `composer audit` dependency check, and a deploy job —
the one piece of this project's automation that can actually execute,
since GitHub's runners have real internet access unlike this
development sandbox.

### Added — Deployment scripts
`scripts/deploy.sh` (pull → install → migrate → rebuild every cache →
restart PHP-FPM → `queue:restart`, stopping at the first failure via
`set -euo pipefail`), `scripts/rollback.sh` (deliberately does not
auto-rollback database migrations — some in this project are additive
by design), `scripts/install.sh`.

### Added — Security hardening (OWASP)
A `SecurityHeaders` middleware (CSP, X-Frame-Options,
X-Content-Type-Options, Referrer-Policy, Permissions-Policy,
conditional HSTS) applied globally. A real, tighter `auth` rate
limiter (10/min, IP+identifier keyed) closing a genuine gap —
login/OTP/password-reset previously shared only the generic 60/min
API-wide throttle. Matching `limit_req` zones added to nginx as
defense-in-depth.

### Added — Monitoring & Error tracking
A real deep `GET /api/v1/health` (database/cache/queue connectivity —
distinct from Laravel's own `/up`, which only confirms the app
booted). A real, dependency-free webhook-based `ErrorTrackingService`
wired into the exception pipeline (no SDK, since `composer install`
is blocked in this sandbox).

### Added — Automated backups
A real `pg_dump`-based `backup:database` command (custom-format dump,
restorable directly with `pg_restore`) with retention pruning,
scheduled daily via `routes/console.php`.

### Added — Database optimization & indexing
5 new composite indexes on the date/status columns Reports/Dashboards/
AI Insights actually query. 3 originally-planned indexes were checked
first and found to already exist from earlier sprints — not
duplicated.

### Fixed — two more real gaps in the migration-verification shim
`useCurrent()` and `longText()` were missing, needed for the new
`failed_jobs` table — the same kind of fix CRM Sprint 3, the HR
sprint, and others each made rather than working around.

### Added — Production documentation
`docs/INSTALLATION_GUIDE.md`, `docs/DEPLOYMENT_GUIDE.md`,
`docs/ADMIN_GUIDE.md`, `docs/BACKUP_RESTORE_GUIDE.md` — all written in
full, cross-referenced against real existing docs (verified those
references resolve to real files, not assumed).

### Verified
- All 84 migrations (82 prior + 2 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`.
- A single `/v1` prefix wraps all 354 routes — API versioning
  consistency confirmed directly, not assumed.

### Fixed mid-sprint (two real bugs in this sprint's own new code)
A `dropIndex()` call using the wrong argument convention for this
codebase (column array instead of the real index-name string this
project's shim expects) — caught and fixed before shipping. A Docker
Compose override using `deploy.replicas: 0` (Swarm-only, silently does
nothing in plain `docker compose up`) — replaced with the correct
`profiles` mechanism.

### Tests
`HealthCheckTest` (2 cases), `SecurityHardeningTest` (2 cases: real
headers on every response, the real rate limiter actually blocking
after its stated limit), `ErrorTrackingServiceTest` (3 unit cases via
`Http::fake()`).

### Explicitly still out of scope
Nothing in this sprint has actually executed outside this development
sandbox — no real GitHub Actions run, no real server deployment, no
real backup taken from a live database, no real Docker image built
(`composer install` reaching Packagist has been blocked throughout
every sprint in this sandbox). A true Sentry/Bugsnag SDK integration
(vs. the real, working webhook approach here) needs `composer install`
to work first. No blue-green/zero-downtime deploy automation beyond a
simple restart. No WAL-based point-in-time recovery (a
database-server-level concern, documented as such, not application
code).

---

## [1.6.0] — AI Assistant Full Completion

A second, deliberately non-duplicative pass over the AI Assistant
module, completing the remaining requested scope without rebuilding
[1.5.0]'s work: a second real LLM provider, real per-tenant provider
selection, AI Insights and Report Summaries, real Automation
Suggestions and AI Notifications, an AI Activity Log, AI Settings, and
AI Prompt Management. Full detail: `docs/AI_ASSISTANT_SPRINT.md`
(updated in place to cover both sprints).

### Added — a second real provider: OpenAI
`OpenAiLlmProvider`, built with the same rigor as `AnthropicLlmProvider`
— no SDK, no hardcoded key, a `RuntimeException` on any failure rather
than garbage. `api.openai.com` is not in this sandbox's allowed
network domains at all (unlike Anthropic), so this integration has
never even been reachable to attempt here — its request shape is
still written directly from OpenAI's public API reference and locked
in via `Http::fake()` tests asserting the exact URL, headers, and body.

### Added — real per-tenant provider selection
`ai_settings.provider_override` lets a tenant choose which
platform-configured provider they use, resolved safely at request time
in `AiServiceProvider`. The override can only ever select a provider
the platform has real credentials for — an override pointing at an
unconfigured provider falls back to the platform default silently,
never errors, verified by a dedicated test. A tenant can never supply
their own API key through the app.

### Added — AI Dashboard/Sales/Inventory/Financial/CRM Insights & Report Summaries
`AiInsightService` — one real engine behind all five insight types
plus a generic report-summary endpoint, all reusing already-audited
services' real data (`AnalyticsDashboardService`, `ReportService`,
`CrmReportService`) rather than re-deriving numbers. The same
LLM-or-deterministic graceful-degradation pattern chat established
governs every insight.

### Added — real Automation Suggestions & AI Notifications
Three insight types detect real conditions (overdue receivables, low
stock, negative cash) and raise a real `AiSuggestion` the first time
each is true — idempotent by design, verified explicitly that calling
the same insight twice while a condition persists creates exactly one
suggestion. The moment a suggestion is raised, every tenant Owner/Admin
gets a real notification via the existing `NotificationService`.

### Added — AI Activity Log, AI Settings, AI Prompt Management
A dedicated `ai_activity_logs` audit trail (distinct from chat's own
provider/model columns) recording every insight/summary call. Real
per-tenant `ai_settings` (master on/off, insights/notifications/
suggestions on/off, provider override), created lazily on first access
— no backfill needed. Real tenant-editable prompt templates for 6 keys
(chat + 5 insight types), each with a real built-in default — never a
blank prompt — and a real reset-to-default path.

### Verified
- All 82 migrations (80 prior + 2 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`.
- The frontend's embedded JavaScript (now 2,123 lines) continues to be
  extracted and run through `node --check` before being considered
  done.

### Fixed — a real pre-existing documentation bug
`PROJECT_STATUS.md`'s "Current phase" section had accumulated two
stacked sprint-summary paragraphs (Reports & Analytics' and AI
Assistant's) from an incomplete edit two sprints prior — the Reports &
Analytics paragraph was never fully removed when AI Assistant's was
added. Found and fixed this sprint while updating the section for
real, not previously noticed.

### Tests
`AiAssistantExtensionIntegrationTest` (10 cases: settings, the
disabled-insights honest message, real activity-log verification,
suggestion-idempotency with `Notification::fake()`, dismiss/re-dismiss
rejection, prompt resolve/override/reset, invalid-key rejection, and
the two safety-critical provider-override cases — used when real
credentials exist, silently falls back when they don't).
`AiAssistantExtensionTenantIsolationTest` (2 cases). `OpenAiLlmProviderTest`
(4 unit cases via `Http::fake()`, mirroring `AnthropicLlmProviderTest`'s
exact rigor).

### Explicitly still out of scope
Only two real providers implemented (Anthropic, OpenAI); OpenAI never
reachable at all in this sandbox; Automation Suggestions cover 3
conditions, not exhaustive; a dismissed suggestion doesn't auto-reopen
if its condition recurs; Report Summaries take a generic JSON payload
rather than being report-type-aware; no streaming responses,
function-calling, or write actions (a deliberate safety boundary);
record-level scoping.

---

## [1.5.0] — AI Assistant Completion

Brought the AI Assistant from a purely deterministic keyword-matched
Q&A tool to a module with a real, working LLM integration layer, while
keeping the deterministic layer fully intact as the honest default and
the real fallback path. Every module in the original ten-area MVP demo
scope is now at the audited bar. Full detail:
`docs/AI_ASSISTANT_SPRINT.md`.

### Added — a real, provider-agnostic LLM integration layer
`App\Services\Ai\LlmProviderInterface` — one seam every provider
implements and every caller depends on. `NullLlmProvider` (default,
`AI_PROVIDER=none`) is the same real deterministic mode this module
has always run on, not a stub. `AnthropicLlmProvider` — a real
implementation against Anthropic's Messages API built on Laravel's
HTTP client (no SDK, `composer install` still blocked), with its
request shape (headers, body) verified against the real HTTP client
via `Http::fake()`, not asserted without proof.

### Added — expanded real grounding data
The deterministic keyword-matched layer now also covers Purchase (open
POs, outstanding payables), HR & Payroll (headcount, latest payroll
run), and Accounting/Cash (cash position, AR, AP) — modules that
didn't exist when the original five intents were written. Every
figure is still a real, live query.

### Added — free-form understanding & multi-turn reasoning, with graceful degradation
When a provider is configured, real grounding data and real
conversation history (last 10 messages) are handed to the LLM as
context it must use, not invent. Any provider failure (verified
explicitly with a faked 503 response) degrades invisibly to the same
deterministic reply a fully unconfigured install would give — logged,
never surfaced as an error. A real `/ai/status` endpoint and frontend
indicator show which mode is actually active.

### On "provider selection is a business decision"
Named directly in the prior sprint's own Feature Matrix as a gap this
project has no mandate to guess at. Not guessed at here either: the
config defaults to no provider at all. Anthropic is the one reference
implementation built specifically because `api.anthropic.com` is the
one LLM endpoint this sandbox's network policy allows — not a claim
it's the right choice for a real deployment.

### Added — a real audit trail for LLM usage
Two new nullable columns on `ai_messages` — `provider`, `model`. Null
means the deterministic fallback answered; a value means a real LLM
call succeeded and which one.

### Explicitly out of scope — actions beyond answering questions
Every code path in this module answers questions; none create,
update, or delete business data. A deliberate safety boundary named
directly, not deferred as unfinished work — write access for an LLM
is a materially different risk profile than read-only Q&A, and this
project has no mandate to take that on.

### Verified
- All 80 migrations (79 prior + 1 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`.
- The frontend's embedded JavaScript (now 2,002 lines) continues to be
  extracted and run through `node --check` before being considered
  done.

### Fixed mid-sprint (two real bugs in this sprint's own test-writing)
A missing closing brace from an initial edit (a parse error caught
immediately by `php -l`), and unreachable test code placed after a
PHPUnit `expectException()` call that would never have executed —
both caught and fixed before shipping.

### Tests
`AiAssistantMvpTest` extended (2 new cases: the expanded grounded
intents, the status endpoint's default state). `AnthropicLlmProviderTest`
(5 unit cases via `Http::fake()`: unconfigured/configured states, the
exact real request shape, a failed response throwing rather than
returning garbage, a missing key never attempting a network call).
`AiAssistantLlmFallbackTest` (3 feature cases: a configured provider's
reply used and recorded with real metadata, a failed provider call
degrading to the real deterministic reply, the status endpoint
reflecting a configured provider).

### Explicitly still out of scope
Only one real provider implemented (Anthropic); no streaming
responses; no function-calling/tool-use; no write actions of any kind
(deliberate safety boundary); never exercised against the real
Anthropic API end-to-end in this sandbox (no key configured here, and
the application layer never executes at all); record-level scoping.

---

## [1.4.0] — Reports & Analytics Completion

Brought Reports up from a scattered collection of per-module report
endpoints to a real Reports & Analytics module in its own right:
cross-module dashboards, three report categories that never existed
(CRM Reports, Cash Flow, VAT Report), a genuine Custom Report Builder,
and real file export (CSV, XLSX, PDF) and recurring email delivery —
all without a single new Composer package. Full detail:
`docs/REPORTS_ANALYTICS_SPRINT.md`.

### Added — Executive Dashboard & KPI Dashboard
`AnalyticsDashboardService::executiveSummary()` — a real cross-module
snapshot (cash position, AR/AP, sales/purchases this month, open POs,
headcount, open leads, open opportunity value, low-stock count).
`kpiSummary()` — real month-over-month trend deltas, with a `null`
(not fabricated-zero) change percent when the prior period had no
activity.

### Added — CRM Reports, Cash Flow, VAT Report (never existed before)
`CrmReportService` — leads by source/status, opportunities by stage, a
real conversion funnel using the real `source_lead_id` link.
`ReportService::cashFlow()` — real cash-basis movement through the
Cash account, named explicitly as single-account rather than a full
indirect-method statement. `ReportService::vatReport()` — output vs.
input VAT off the existing split accounts.

### Added — Custom Report Builder
`CustomReportService` — a saved, re-runnable definition (source +
columns + filters + optional group-by). The entire safety model is one
allow-list: a source key maps to a real Eloquent model and a fixed
column set, so a saved definition can never become a SQL-injection
vector no matter what a tenant stores in it.

### Added — real, dependency-free PDF & Excel export
`ReportExportService` hand-builds a real minimal XLSX (via PHP's
`ZipArchive`) and a real minimal multi-page PDF (hand-built PDF 1.4
objects/xref/trailer) from scratch — no PhpSpreadsheet, no dompdf/mpdf,
since `composer install` remains blocked. **Rigorously verified, not
just claimed**: `qpdf --check`, `pdfinfo`, `pdftotext`, and `unzip -l`
against actual generated files, including an 80-row/2-page pagination
test — then locked in as automated unit tests. Export wired to the
Custom Report Builder (`?format=csv|pdf|xlsx`) and a new
`/reports/export/{reportKey}` endpoint for naturally-tabular built-in
reports.

### Added — Scheduled Reports
`ScheduledReportService` + `ScheduledReportMail` + a new
`reports:process-scheduled` console command. Deliberately scoped to
saved Custom Reports only, since built-in statement-style reports have
shapes a generic exporter can't represent honestly. Real per-frequency
`next_run_at` computation; `process()` runs every due schedule,
generates the configured format, and emails every recipient.

### Verified
- All 79 migrations (77 prior + 2 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`.
- The frontend's embedded JavaScript (now 1,997 lines) continues to be
  extracted and run through `node --check` before being considered
  done.

### Fixed mid-sprint (a real bug in this sprint's own frontend work)
The first draft of the export button used unauthenticated
`window.open()`, silently incompatible with this app's Bearer-token
auth — caught before shipping and replaced with a proper authenticated
blob-download helper (`downloadExport()`) used consistently everywhere
a file is downloaded.

### Tests
`ReportsAnalyticsIntegrationTest` (6 cases, including the Custom
Report Builder's allow-list rejecting invalid input and Scheduled
Reports' `Mail::fake()`-verified real delivery), `ReportsAnalyticsTenantIsolationTest`
(1 case), `ReportExportServiceTest` (4 unit cases verifying real CSV
content, a structurally valid XLSX, a structurally valid PDF, and real
multi-page pagination) — the same rigor manually verified with
`qpdf`/`pdfinfo` during development, now automated.

### Explicitly still out of scope
Cash Flow is single-account/cash-basis only; statement-style reports
(P&L, Balance Sheet, Cash Flow, VAT) aren't wired to the generic
file-export endpoint; Scheduled Reports cover Custom Reports only; PDF
export can't render Arabic (base Helvetica has no Arabic glyphs); XLSX
has no styling/formulas/multiple sheets; `reports:process-scheduled`
has never executed end-to-end in this sandbox; no drag-and-drop report
designer; record-level scoping.

---

## [1.3.0] — HR & Payroll Module Completion

Built HR & Payroll from zero — no Employee data model existed before
this sprint, only the `hr` role code as an RBAC label — directly to
the same audited bar CRM, Sales, Inventory, Purchase, and Accounting
already carry. Full detail: `docs/HR_PAYROLL_SPRINT.md`.

### Added — Employees, Departments, Designations, Shifts, Holidays
Full Employee CRUD (optional link to a system User via `user_id`, real
termination workflow), reused the existing Platform Admin
`departments` table rather than duplicating it, and three
tenant-editable lookup tables (Designations, Shifts, Holidays).

### Added — Attendance
Real check-in/check-out with shift-aware lateness detection (more than
10 minutes past a shift's `start_time` marks the day 'late'), real
hours-worked computation on check-out, and a manual-mark path for HR.

### Added — Leave Management
Leave Types (tenant-editable, paid/unpaid), per-employee-per-year
Leave Balances auto-provisioned on hire for every active leave type, and
a Leave Request workflow with real balance validation — a paid-leave
request exceeding the remaining balance is rejected outright.
Approving deducts the real balance and marks every day in the range
'on_leave' in Attendance, and notifies the employee via the existing
`NotificationService`.

### Added — Payroll (Salary Structure, Allowances, Deductions, Overtime, Payslips)
`PayrollService::process()` computes real gross-to-net pay for every
active employee in one transaction (basic + allowances + approved
overtime − deductions), generates a real Payslip with full line-item
detail per employee, and posts one real balanced journal entry for the
whole run via new `HrPayrollAccountingIntegrationService` — Dr `5200
Salaries & Wages Expense` (gross), Cr `1000 Cash` (net), Cr `2200
Salaries Payable` (deductions withheld). A single tenant-editable
Salary Components engine backs Allowances and Deductions alike.
Overtime uses a documented 240-hour-month hourly-rate basis with a
configurable rate multiplier. Two new chart-of-accounts entries
(`5200`, `2200`) seeded at registration, with a `hr:provision-defaults`
backfill command for existing tenants — the same guarded-backfill
pattern the Accounting sprint used for `2110 VAT Recoverable`.

### Added — Payroll Report
`ReportService::payrollSummary()` — real per-run totals plus a real
department breakdown. New `GET /reports/payroll-summary` endpoint.

### Added — Employee Self-Service
A dedicated `ess.*` permission module: every endpoint resolves the
calling user's own Employee record server-side
(`employees.user_id = auth user id`), never a client-supplied id. Own
profile, own attendance (+ self check-in/check-out), own leave
requests (view + submit), own payslips.

### Added — Recruitment (basic)
Job Openings → Candidates → Job Applications, with a real status
pipeline. Hiring an application (`RecruitmentService::hire()`) creates
the actual Employee record from the candidate's details — a real
integration, not a status flag.

### Added — Performance Reviews (basic)
Cycle-based (one review per employee per cycle, a real unique
constraint), with a genuine draft → submitted → acknowledged
lifecycle — submission is rejected without a rating first.

### Verified
- All 77 migrations (73 prior + 4 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`, RLS enabled and forced on all 19
  new tables.
- The frontend's embedded JavaScript (now 1,750 lines) continues to be
  extracted and run through `node --check` before being considered
  done.

### Fixed — a real gap in the migration verification tool itself
`tools/db-verify/schema_shim.php` was missing `time()`/`dateTime()`
column-type support, needed for `shifts.start_time`/`end_time` and
`attendances.check_in`/`check_out` — the same kind of fix CRM Sprint 3
made (a missing `date()` method) rather than working around it with a
different column type.

### Tests
`HrPayrollModuleIntegrationTest` (8 cases, including the full payroll
run → payslip → balanced accounting posting chain and the recruitment
hire → real Employee integration), `HrPayrollExtensionTenantIsolationTest`
(2 cases), `HrReportsDashboardAndEssTest` (2 cases, including Employee
Self-Service's server-side scoping) — integration depth, consistent
with every prior audited-bar sprint.

### Explicitly still out of scope
GOSI/income-tax computation (real regulatory input this project has
never been given), configurable standard working hours (240-hour-month
is a documented constant), cancelling an approved leave request
(pending-only this sprint), payslip PDF export/email delivery,
interview scheduling/offer letters/onboarding checklists, 360-degree
performance feedback, record-level scoping, biometric/geolocation
attendance.

---

## [1.2.0] — Accounting Module Completion

Brought Accounting's own core engine up to the audited bar CRM, Sales,
Inventory, and Purchase already carry — real journal entry reversal,
split input/output VAT accounts (closing a gap the Sales and Purchase
sprints each named directly rather than hid), and two real financial
statements (Income Statement, Balance Sheet) computed from actual
journal entry lines. Full detail: `docs/ACCOUNTING_MODULE_SPRINT.md`.

### Added — Real journal entry reversal
`AccountingService::reverseEntry()` creates a brand-new entry with
every line's debit/credit swapped rather than editing or deleting the
original in place; the original is marked `is_reversed` and linked to
the new entry via `reversed_by_entry_id`. Auto-posted entries
(`source_type` set — from Sales/Purchase/Inventory integrations) are
explicitly rejected: correcting those means correcting the source
document, not the accounting side in isolation.

### Added — Split input/output VAT accounts
New `2110 VAT Recoverable` account (asset), repointing
`PurchaseAccountingIntegrationService` to post input VAT there instead
of netting through Sales' `2100 VAT Payable`. New tenants get both
accounts automatically; a new `accounting:provision-defaults` console
command (mirroring `crm:provision-defaults` exactly) backfills the
account for tenants that registered before this sprint, without
duplicating the nine accounts they already have.

### Added — Two real financial statements
`ReportService::incomeStatement(?from, ?to)` (revenue − expenses over
an optional period) and `ReportService::balanceSheet()` (an as-of-today
snapshot, assets = liabilities + equity, retained earnings rolled into
equity, with a real `balanced` boolean computed at a 0.01 tolerance,
not assumed). New `GET /reports/income-statement` and
`GET /reports/balance-sheet` endpoints.

### Verified
- All 73 migrations (72 prior + 1 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`.
- The frontend's embedded JavaScript (now 1,471 lines) continues to be
  extracted and run through `node --check` before being considered
  done.

### Tests
`AccountingModuleIntegrationTest` (6 cases: VAT account provisioning
and backfill, Purchase's VAT split posting to the new account,
reversal balance/rejection cases including the auto-posted-entry
restriction, financial statements against real posted sales activity),
`AccountingExtensionTenantIsolationTest` (2 cases: raw-query
invisibility, independent per-tenant entry numbering across an entry-
then-reversal sequence), `AccountingStatementsReportsTest` (1 case:
real data-shape smoke test, a zero-activity tenant's balance sheet
balancing, and the date-range filter not erroring on an empty range).

### Explicitly still out of scope
Accounting periods/period-close, multi-currency, budget vs. actual
reporting, a KSA-specific COA template wired to ZATCA (belongs to the
separate legacy HTML application), record-level scoping, editing a
posted entry in place (by design), statement export/scheduling.

---

## [1.1.0] — Purchase Module Completion

Brought Purchase from MVP-demo depth up to the audited bar CRM, Sales,
and Inventory carry — Supplier Bills (Accounts Payable), Supplier
Payments, Debit Notes, Purchase Returns, a Purchase Dashboard, and two
new Purchase Reports, closing the liability-side accounting gap this
project had carried since the Sales and Inventory sprints built their
own equivalents. Full detail: `docs/PURCHASE_MODULE_SPRINT.md`.

### Added — Supplier Bills (the real liability event)
Full CRUD, creatable standalone or `createFromGoodsReceipt()` — bills
against what was actually received (quantities/costs from the Goods
Receipt), not the original PO. Approving posts a real, balanced journal
entry (`Dr Inventory`, `Dr VAT recoverable`, `Cr Accounts Payable`).

### Added — Supplier Payments (real multi-bill allocation)
`SupplierPayment` + `SupplierPaymentAllocation`, mirroring
`CustomerPaymentService` exactly: one payment can be allocated across
multiple bills. `SupplierBillService::recalculateStatus()` derives
status purely from real paid/credited totals. The single-bill "Record
Payment" convenience action delegates to the same real path.

### Added — Debit Notes
The Purchase-side mirror of Credit Notes: issued against a specific
bill, validated against that bill's actual current outstanding balance
(rejected if it would exceed it). Issuing posts the exact reversing
entry of the original bill posting.

### Added — Purchase Returns
Returning goods moves stock back out to the supplier. When a
`supplier_bill_id` is provided, returning also auto-generates and
issues a matching Debit Note — the same reasoning `SalesReturnService`
applies for Credit Notes.

### Added — Purchase Dashboard and two new Purchase Reports
`PurchaseDashboardService` (document counts, spend/payments this month,
outstanding payables, overdue bill count) mirrors `SalesDashboardService`
exactly. `ReportService::purchaseBySupplier()` and `agingPayables()`
(same bucket structure as `agingReceivables()`).

### Added — real integration, not coexistence
New `PurchaseAccountingIntegrationService` posts real, balanced journal
entries for bill approval (Dr Inventory/Dr VAT/Cr AP), payment made (Dr
AP/Cr Cash), and debit note issued (the reverse) — mirroring
`SalesAccountingIntegrationService`'s exact pattern including its
loud-failure-on-missing-account behavior. The receivable/payable
accounting loop is now closed on both sides against the same real
chart of accounts. One deliberate simplification named directly: input
and output VAT net through the same `2100` account rather than
separate accounts.

### Verified
- All 72 migrations (67 prior + 5 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`. RLS confirmed enabled and forced on
  every new table.
- The frontend's embedded JavaScript (now 1,424 lines) continues to be
  extracted and run through `node --check` before being considered
  done.

### Fixed mid-sprint (documentation process, not application code)
A route-file edit briefly deleted the Inventory module's two report
routes while adding Purchase's — caught via a direct check before
moving on, both route sets confirmed present in the final file.

### Tests
`PurchaseModuleIntegrationTest` (4 cases, including one full real PO →
Goods Receipt → Supplier Bill → Approve → Payment → Return → Debit Note
flow with stock/accounting/balance assertions at every step),
`PurchaseExtensionTenantIsolationTest` (2 cases),
`PurchaseReportsAndDashboardTest` (1 case) — integration depth,
consistent with Sales and Inventory.

### Explicitly still out of scope
Record-level scoping for Purchase, split input/output VAT accounts,
purchase requisitions/approval workflows, landed cost allocation,
partial billing against a single Goods Receipt, supplier payment
reversal.

---

## [1.0.0] — Inventory Module Completion

Brought Inventory from MVP-demo depth up to the audited bar CRM and
Sales carry — Categories, Units, Brands, Barcode Support, Stock
Transfers, Stock Adjustments, Goods Receiving, Goods Issue, Low Stock
Alerts, and richer Inventory Reports, with real integration into
Purchase, Sales, and Accounting. Full detail:
`docs/INVENTORY_MODULE_SPRINT.md`.

### Fixed — a real design bug from 0.8.0, mirroring the 0.9.0 fix
`PurchaseOrderService::receive()` used to move stock directly,
conflating Purchase's own logic with the warehouse event. Fixed by
introducing `GoodsReceipt` as the real Inventory-side stock-in event —
Purchase now creates and receives one rather than touching stock
itself. A new `PurchaseMvpTest` case proves the redesigned flow creates
a real, linked `goods_receipts` row with a `stock_movements` entry
whose `reference_type` is `'goods_receipt'`, not an untracked direct
move.

### Added — Categories, Units, Brands
Real tenant-editable entities with full CRUD, replacing the MVP
sprint's plain-string `products.category`/`unit` fields. The old string
columns are kept (nullable, unused going forward) for backward
compatibility — nothing from prior sprints breaks.

### Added — Barcode Support
`products.barcode` (unique per tenant) plus a real
`GET /inventory/products-by-barcode` lookup endpoint —
`InventoryService::findByBarcode()`, not just a stored field nobody
can query by.

### Added — Stock Transfers
Move stock between two warehouses atomically. Rejects transferring a
warehouse to itself and rejects completing twice.

### Added — Stock Adjustments (real entity, joining the MVP's quick-adjust endpoint)
A real, auditable `StockAdjustment` with a draft→approved workflow:
multi-line, reasoned, and — approving posts a real accounting entry
(`Dr Operating Expenses / Cr Inventory` for a write-off, the reverse for
found stock), valued at each product's `cost_price`. The MVP sprint's
simple `POST /inventory/stock-adjustments` quick-adjust endpoint is
unchanged and still works, at a distinct path (`/inventory/adjustments`
for the new tracked entity).

### Added — Goods Receiving and Goods Issue
`GoodsReceipt` (the real stock-in event, described in Fixed above) and
`GoodsIssue` (real stock-out for internal consumption/samples/damage,
distinct from a Sales Delivery Note). Issuing goods posts a real
`Dr Expense / Cr Inventory` entry.

### Added — Low Stock Alerts
`InventoryService::adjustStock()` now fires a real notification to
every Inventory/Company Owner user whenever a stock decrease crosses at
or below a product's reorder point — from the one central place every
stock decrease in the whole product already flows through (adjustments,
transfers, goods issues, sales deliveries, returns), so nothing can
decrease stock without the alert logic seeing it.

### Added — two new Inventory Reports
`ReportService::stockByWarehouse()` and `inventoryByCategory()` —
extending the MVP sprint's inventory valuation report.

### Added — real integration, not coexistence
- **Purchase**: the Goods Receipt redesign above.
- **Sales**: verified unchanged and correct alongside the new Low Stock
  Alert hook.
- **Accounting**: new `InventoryAccountingIntegrationService`, mirroring
  `SalesAccountingIntegrationService`'s exact pattern — Stock
  Adjustments and Goods Issues post real, balanced journal entries
  against the tenant's actual chart of accounts, traceable via
  `journal_entries.source_type`/`source_id`.

### Verified
- All 67 migrations (60 prior + 7 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`. RLS confirmed enabled and forced on
  every new table.
- The frontend's embedded JavaScript (now 1,263 lines) continues to be
  extracted and run through `node --check` before being considered
  done, same practice the Sales sprint introduced.

### Tests
`InventoryModuleIntegrationTest` (4 cases, including one full real
Category/Unit/Brand/Barcode → Warehouse → Goods Receipt → Transfer →
Adjustment → Issue → Low Stock Alert flow with stock/accounting
assertions at every step), `InventoryExtensionTenantIsolationTest`
(2 cases), `InventoryReportsTest` (1 case), plus one new case in
`PurchaseMvpTest` — 8 cases at integration depth.

### Explicitly still out of scope
Record-level scoping for Inventory, Purchase-side accounting
integration (Accounts Payable on Goods Receipt), FIFO/weighted-average
costing, batch/lot and serial tracking, barcode label printing, partial
goods receiving.

---

## [0.9.0] — Sales Module Completion

Brought Sales from MVP-demo depth (0.8.0) up to the audited bar CRM's
three sprints carried — exhaustive integration tests, real cross-module
integration, no known shortcuts. Full detail: `docs/SALES_MODULE_SPRINT.md`.

### Fixed — a real design bug from 0.8.0, not carried forward
`SalesInvoiceService::issue()` used to move stock out, conflating the
financial event (invoicing) with the warehouse event (delivery). Fixed
by introducing Delivery Notes as the real stock-out event; invoice
issuance is now purely financial. `SalesModuleIntegrationTest` asserts
explicitly that stock changes exactly once, at delivery.

### Added — Delivery Notes
- Full CRUD, creatable standalone or from a confirmed Sales Order.
- `DeliveryNoteService::deliver()` — the real inventory-affecting event,
  rejects double-delivery.

### Added — Customer Payments (replacing the 0.8.0 direct paid-amount bump)
- Real `CustomerPayment` + `PaymentAllocation` entities — one payment
  can be allocated across multiple invoices.
- `SalesInvoiceService::recalculateStatus()` derives invoice status
  purely from real paid/credited totals, never drifts out of sync.
- The single-invoice "Record Payment" action on an invoice still works,
  now as a thin wrapper over the same real allocation path.

### Added — Credit Notes
- Issued against a specific invoice, validated against that invoice's
  *actual current* balance (rejected if it would exceed it).
- Issuing posts the exact reversing journal entry of the original
  invoice posting and reduces `sales_invoices.credited_amount` — a
  credit note reduces the obligation, it is not a payment.

### Added — Sales Returns
- Receiving a return moves stock back in and, when linked to an
  invoice, **auto-generates and issues a matching Credit Note** —
  deliberate, so a physical return always gets a financial counterpart
  rather than depending on a manual follow-up step.

### Added — Sales Dashboard and three new Sales Reports
- `SalesDashboardService`: document counts across all six document
  types, quotation win rate, this month's revenue/payments, outstanding
  receivables, real overdue-invoice count.
- `ReportService::salesByCustomer()`, `salesByProduct()`, and a real
  aging-receivables report (Current/1-30/31-60/61-90/90+ days, computed
  from actual due dates).

### Added — real integration, not coexistence
- **Accounting**: new `SalesAccountingIntegrationService` posts real,
  balanced journal entries for invoice issuance (Dr AR/Cr Revenue/Cr
  VAT), payment receipt (Dr Cash/Cr AR), and credit note issuance (the
  reversing entry) — against the tenant's actual chart of accounts.
  `journal_entries` gained `source_type`/`source_id` for traceability.
  Posting fails loudly if a required standard account is missing —
  never silently skipped.
- **CRM**: `quotations.opportunity_id` — a Quotation can now originate
  from a real Opportunity.
- **Inventory**: re-homed correctly onto Delivery Notes and Sales
  Returns (see the Fixed section above).

### Verified
- All 60 migrations (54 prior + 6 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`. RLS confirmed enabled and forced on
  all five new tables.
- New this sprint: the frontend's embedded JavaScript is now extracted
  and run through `node --check` before being considered done, not just
  visually reviewed.

### Tests
`SalesModuleIntegrationTest` (5 cases, including one full real
Opportunity→Quotation→Order→Delivery→Invoice→Payment→Return→CreditNote
flow with stock/accounting/balance assertions at every step),
`SalesExtensionTenantIsolationTest` (2 cases), `SalesReportsAndDashboardTest`
(1 case) — 8 cases at integration depth, not smoke-level.

### Explicitly still out of scope
Record-level scoping for Sales, PDF generation, ZATCA e-invoicing,
partial delivery of a single Sales Order, payment reversal.

---

## [0.8.0] — Client-Ready MVP Demo

An explicit priority reset, stated directly rather than left implicit:
breadth across all ten requested areas within one pass, not another
fully-audited module sprint. Smoke-level tests and module-level (not
record-level) RBAC on the six new modules below — a real, deliberate
tradeoff, not a quality regression in what shipped. Full detail and the
explicit Version 2 Backlog: `docs/MVP_DEMO.md`.

### Added — Inventory
- Products, Warehouses, Stock Levels, Stock Movements.
- `InventoryService::adjustStock()` — atomic (row-locked), rejects any
  movement that would take stock negative.
- A default warehouse now provisioned at registration.

### Added — Purchase
- Suppliers, Purchase Orders with line items.
- `PurchaseOrderService::receive()` — the real inventory-affecting
  event: moves stock in, rejects double-receiving.

### Added — Sales
- Quotation → Sales Order → Invoice, a real three-stage document chain
  with genuine conversion preconditions (quotation must be accepted;
  order must be confirmed), not three independent lists.
- `SalesInvoiceService::issue()` moves stock out; `recordPayment()`
  derives paid/partial status from actual paid amount, rejects
  overpayment.
- VAT computed per line at that line's own rate
  (`CalculatesDocumentTotals` trait, shared across all four document
  types including Purchase Orders).

### Added — Basic Accounting
- A seeded, real chart of accounts (9 standard accounts) — provisioned
  at registration via `AccountingProvisioningService`.
- `AccountingService::createEntry()` — genuine double-entry validation
  (debits must equal credits; each line needs exactly one of
  debit/credit set), rejected outright, never auto-corrected.

### Added — Reports
- Four real cross-module reports: Sales summary, Purchase summary,
  Inventory valuation, Trial Balance (`ReportService`).

### Added — AI Assistant (basic)
- Real, not fabricated, and explicitly not an LLM: keyword-matched
  intents answered with genuinely computed numbers from the tenant's
  own data (leads, customers, opportunities, sales, inventory).
  Conversation history persists. Full LLM integration is the clearest
  Version 2 item in this release.

### Added — Frontend (`/app`)
- This project's first full tenant-facing console, extending the Super
  Admin Console's vanilla-JS-over-JSON-API pattern from one
  platform-operator screen to the whole product.
- Two reusable engines instead of 15 bespoke screens: a generic
  list+create view and a generic document view (dynamic line-item
  builder, live VAT/total preview, status-driven workflow actions).
- Purpose-built screens where a generic table didn't fit: Dashboard
  (real metric cards from every module), Stock, Journal Entries, AI
  Assistant chat, Users/Roles, Company Settings.
- A new public `GET /public/tenants/lookup` endpoint and an inline
  self-service registration form — both added specifically so the
  login screen is actually usable for a demo without needing to know a
  raw tenant UUID.

### Verified
- All 54 migrations (48 prior + 6 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`, continuing standing practice. RLS
  confirmed enabled and forced on every new table, spot-checked against
  real cross-tenant write rejection.

### RBAC
- Six new permission modules (`inventory`, `purchase`, `sales`,
  `accounting`, `reports`, `ai`) with sensible default role grants —
  Accounting restricted to Owner/Admin/Accountant (most sensitive
  module), AI Assistant available to every seat.

### Explicitly deferred to Version 2 (see `docs/MVP_DEMO.md` for the full list)
Record-level scoping on the six new modules; auto-posting journal
entries from Sales/Purchase; a real LLM-backed AI Assistant; HR and
Payroll; Billing & Subscription automation; PDF generation; ZATCA
e-invoicing; a project-wide frontend architecture decision.

---

## [0.7.0] — CRM Sprint 3: Opportunities

Requested as part of a broader "complete the twelve-module MVP
autonomously" sprint; per that sprint's own stopping rule, this entry
covers the one full sprint actually completed (CRM Sprint 3) rather than
nine shallow module stubs. See `ROADMAP.md`'s "A direct note on the
twelve-module MVP" for the full reasoning.

### Added
- **Opportunity Stages**: tenant-editable pipeline (6 seeded defaults:
  Qualification → Needs Analysis → Proposal → Negotiation → Closed
  Won/Lost), each with a `default_probability` new opportunities
  inherit. `CrmProvisioningService` extended with a backfill-safe path
  so tenants that registered before this sprint get just the new
  stages, not duplicated sources/statuses.
- **Opportunities**: full CRUD against a required `customer_id`
  (`lead_id` kept for provenance only), auto-generated sequential
  numbers (`OP-000001`, `SequenceService`'s third reuse), amount,
  probability, expected close date, assignment.
- **Real enforced business rule**: moving an opportunity into a
  `is_won`/`is_lost` stage auto-sets `closed_at` and logs a dedicated
  `won`/`lost` timeline event — `OpportunityService::handleStageChange()`
  is the single place this happens.
- **Opportunity Activity Timeline**, mirroring Lead's and Customer's.
- **`OpportunityPolicy`** — third consecutive exact repeat of the
  record-level scoping pattern (Sales sees/manages only their own).
- CRM Dashboard extended: open pipeline count, win rate, won/lost this
  month, weighted open pipeline value, full stage-by-stage breakdown.
  CRM Navigation extended with an Opportunities entry.
- `OpportunityManagementTest` (5 cases), `OpportunityStageManagementTest`
  (3 cases), `CrmOpportunityTenantIsolationTest` (2 cases) — 10 new test
  cases.

### Verified, and the tool itself needed a fix
- All 48 migrations (44 prior + 4 new) run cleanly against real
  PostgreSQL via `tools/db-verify/`. RLS confirmed enabled and forced on
  all three new tables; a real cross-tenant write against `opportunities`
  was rejected exactly as expected.
- The verification run **failed on the first attempt** — not a bug in
  the migration, but a gap in the verification tool's own Blueprint
  shim: `$table->date('expected_close_date')` was the first use of
  `date()` in this project, a method the shim (built by enumerating only
  the original 39 migrations) didn't implement. Real Laravel has it; the
  hand-built shim didn't yet. Fixed by adding the one missing method,
  documented in `tools/db-verify/` and `CRM_SPRINT_3_OPPORTUNITIES.md`
  rather than silently patched.

### Scope note
The twelve-module MVP was not completed in this sprint — see
`ROADMAP.md` for the explicit reasoning and the realistic remaining
module sequence (Inventory next, since both CRM Sprint 4 and Sales/
Purchase depend on it).

---

## [0.6.0] — CRM Sprint 2: Customers

The prior roadmap listed three CRM Sprint 2 candidates (Opportunities,
Customers, Quotations) without a business-priority pick. Decisively
scoped to Customers — the roadmap's own text already named it the
clearest gap ("a won lead should become something") — rather than a
partial spread across all three.

### Added
- **Customers**: full CRUD, auto-generated sequential customer numbers
  (`CU-000001`, reusing `SequenceService` from CRM Sprint 1 — the first
  real proof that reusability decision paid off), account manager
  assignment, credit limit / payment terms, VAT number.
- **Customer Activity Timeline**: system-generated (`created`,
  `converted_from_lead`, `account_manager_changed`) + manually logged
  entries, mirroring Lead's timeline exactly.
- **Lead → Customer conversion**: `POST /crm/leads/{lead}/convert-to-
  customer`. Real business rules enforced: only a lead whose status is
  marked `is_won` can convert, and only once. `LeadConversionService`
  is its own service (touches both entities, belongs to neither).
  Bidirectional linkage: `customers.source_lead_id` and
  `leads.converted_to_customer_id`/`converted_at`.
- **`CustomerPolicy`**: record-level scoping mirroring `LeadPolicy` —
  Sales sees/manages only customers they're the account manager for.
- CRM Dashboard extended with real customer totals, new-this-month, and
  conversions-this-month. CRM Navigation extended with a Customers entry.
- `CustomerManagementTest` (6 cases), `LeadConversionTest` (5 cases),
  `CrmCustomerTenantIsolationTest` (2 cases) — 13 new test cases.

### Verified
- All 5 new migrations (44 total, up from 39) run cleanly against real
  PostgreSQL via `tools/db-verify/`, continuing last sprint's practice
  rather than a one-off. RLS confirmed enabled and forced on both new
  tables; a real cross-tenant write against `customers` was rejected
  exactly as expected (`ERROR: new row violates row-level security
  policy`).

### No new dependencies, no new architectural patterns
Every piece of this sprint follows CRM Sprint 1's established shape
exactly (Repository/Service/Controller/Request/Resource/Policy) — the
one addition, `LeadConversionService`'s cross-entity role, is a clean
single-responsibility application of the same pattern, not a new one.

---

## [0.5.0] — Database Verification Sprint

No new business features — this sprint verified the database layer for
real instead of adding to it, per `ROADMAP.md`'s own top-ranked "Next
sprint" candidate.

### Added
- `tools/db-verify/` — a permanent, reusable tool: a minimal Schema/
  Blueprint/DB compatibility shim that runs the real migration files
  verbatim against real PostgreSQL, for environments (like this
  sandbox) where `composer install` cannot reach Packagist. Includes
  its own `README.md` with usage and a documented gotcha (leftover
  open connections causing false-failure retries).
- `docs/DATABASE_VERIFICATION.md` — full methodology and results.

### Verified (not code changes — empirical confirmation of existing design)
- All 39 migrations run cleanly against real PostgreSQL 16, in order,
  zero errors. First time in this project's history the schema has
  been proven to actually build.
- Row-Level Security enabled on exactly the 26 tables that should have
  it (of 29 total), confirmed against a live database.
- Cross-tenant read isolation: a real query bound to Tenant A's session
  returned 0 rows for Tenant B's data; the same query returned 1 row
  once `is_super_admin` was set.
- Cross-tenant write protection: an `INSERT` claiming to belong to
  Tenant B while bound to Tenant A's session was rejected by RLS's
  `WITH CHECK` (`ERROR: new row violates row-level security policy`);
  the equivalent same-tenant insert succeeded.
- The two critical fixes from `TENANT_ISOLATION_REVIEW.md` (registration's
  mid-transaction tenant binding; Super Admin's platform-level
  `tenant_id IS NULL` insert requiring the `is_super_admin` flag) were
  each independently re-verified against real RLS.
- `SequenceService`'s hand-written raw SQL (atomic per-tenant
  numbering) and the `citext` case-insensitive email uniqueness both
  confirmed correct against real data.

### Confirmed as an environment constraint, not a project defect
- `composer install` fails with HTTP 403 from Packagist in this
  sandbox — outside the network policy's allowed domains. The
  application/HTTP layer (Controllers, Services, Middleware, Eloquent
  model events, the 27-file PHPUnit suite) remains unverifiable here
  until run in an environment with real internet access.

### No bugs found
Unlike each of the four preceding sprints (which each independently
found a real bug via code review), this sprint's real execution of the
database layer found none — a good, specific signal about the schema's
soundness, not a claim about the still-unverified application layer.

---

## [0.4.0] — Super Admin Console

### Added
- Platform-level tenant management: `GET /admin/platform/tenants`,
  `GET /admin/platform/tenants/{tenant}`, `POST .../suspend`,
  `POST .../reactivate`.
- Real, cross-tenant platform metrics: `GET /admin/platform/metrics`
  (tenant counts by status, total users, total leads, new tenants this
  month, 6-month signup trend). No revenue/MRR figure — no billing
  engine exists to source one honestly.
- `tenants.suspension_reason` and `tenants.suspended_by_user_id`
  columns — suspension now records who and why.
- `SuperAdminTenantService`: suspending a tenant now revokes every
  active session for every user in it and logs to both the platform's
  own audit trail and the affected tenant's own activity log.
- `PlatformMetricsService`.
- `EnsureSuperAdmin` middleware and a dedicated, non-tenant-scoped route
  group for the console.
- **This project's first frontend page**: `resources/views/super-admin/
  console.blade.php` — a real, functional login + metrics + tenant
  table with working suspend/reactivate actions, vanilla JS over the
  existing JSON API. Required adding `routes/web.php` (previously
  absent). Scoped deliberately narrowly — see `docs/SUPER_ADMIN_CONSOLE.md`.
- `SuperAdminConsoleTest` (10 cases), `PlatformMetricsServiceTest` (unit).

### Fixed
- A bug caught before shipping (not left in): `SuperAdminTenantService`
  originally called `->each()` directly on an Eloquent query builder —
  that method doesn't exist there. Fixed to `->get()->each(...)`.

### Documentation
- Added `docs/SUPER_ADMIN_CONSOLE.md`.
- Updated `PROJECT_STATUS.md`, `FEATURE_MATRIX.md`, `ROADMAP.md` to
  reflect this sprint (see those files' own histories for exact deltas
  — not duplicated here).

---

## [0.3.0] — CRM Sprint 1: Lead Management Foundation

### Added
- Lead Management: full CRUD with every field specified (lead number,
  company/first/last/Arabic name, email, phone, WhatsApp, country,
  city, source, status, assignee, expected revenue, probability,
  priority, notes, attachments, created/updated by).
- Lead Source Management, Lead Status Management — tenant-editable
  catalogs, same pattern as Roles.
- Lead Assignment with real notifications.
- Lead Activity Timeline — system-generated + manually logged entries.
- CRM Dashboard (real aggregates) and CRM Navigation (permission-
  filtered menu API).
- `SequenceService` — generic, reusable per-tenant atomic numbering
  (`LD-000001`), designed for reuse by future numbered documents.
- `LeadPolicy` — this project's first record-level authorization
  (Sales sees/edits only their own assigned leads).
- `CrmProvisioningService` + `crm:provision-defaults` backfill command.

### Fixed
- `Controller::ok()` silently skipped the `{"data": ...}` envelope for
  every plain-array response — affected most message-only responses
  project-wide (present since 0.1.0) and both dashboards' composite
  payloads. Fixed at the root; regression-tested.
- Two `tenant_id`-on-pivot-write bugs (`role_permissions` sync calls,
  `user_branches` sync calls) that would have made permission grants
  and branch assignments invisible under RLS.

---

## [0.2.0] — Platform Administration

### Added
- Dashboard (honest "not yet available" widgets for unbuilt ERP
  domains; real widgets for what exists), Company Profile/Settings,
  Branch/Department Management, extended User Management (admin
  password reset, branch assignment, MFA status), Notification Center
  (in-app + email real; SMS/WhatsApp/push transport stubbed with
  explicit `TODO(ops)` markers), Activity Log with module attribution,
  browser parsing, and CSV export.
- `Auditable` trait extended to write both `audit_logs` (diff) and
  `activity_logs` (human feed) from one model event, with sensitive-
  field redaction and noisy-field suppression.

---

## [0.1.0] — Foundation: Tenancy + Authentication + RBAC

### Added
- Multi-tenant architecture: PostgreSQL Row-Level Security +
  application-layer scoping (`BelongsToTenant`).
- Authentication: Sanctum access tokens + custom rotating refresh
  tokens with theft detection, OTP-based MFA (opaque ticket, never a
  raw user ID), company registration, forgot/reset password, email
  verification, multi-device session management.
- RBAC: database-driven roles/permissions, ten default role codes
  provisioned per tenant, fully editable afterward.
- Repository Pattern + Service Layer established as the project's
  standing architecture for every module since.

### Reviewed
- Dedicated tenant-isolation audit (`docs/TENANT_ISOLATION_REVIEW.md`):
  5 findings, 2 critical (registration would have failed outright under
  real RLS), all fixed.

---

## Known unversioned state

As of 0.5.0, the **database layer** has been run for real against
PostgreSQL 16 (see that entry above and `docs/DATABASE_VERIFICATION.md`).
The **application layer** — `composer install`, a real migration via
`php artisan migrate`, and the test suite — has still never executed,
blocked by this sandbox's network policy (Packagist unreachable), not
by anything in the code. Treat every version's application-layer claims
as "should work," and its database-layer claims as "verified working,"
until an environment with real internet access closes the remaining gap.
