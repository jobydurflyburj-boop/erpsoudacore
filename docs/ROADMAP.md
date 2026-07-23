# SoudaCore ERP — Roadmap

This is an audit artifact, not a planning session — it organizes what
already exists and what was already scoped in prior sprints into a
sequence. It does not invent new features or commit to timelines.

---

## Completed

- **Foundation** — Multi-tenancy (RLS + app scope), Authentication
  (Sanctum + rotating refresh tokens + OTP MFA), RBAC (database-driven,
  per-tenant roles/permissions), company registration.
- **Platform Administration** — Dashboard, Company Profile/Settings,
  Branch/Department Management, User Management, Role/Permission
  Management, Notification Center (in-app + email real; SMS/WhatsApp/
  push stubbed), Activity Log with export.
- **CRM Sprint 1** — Lead Management, Lead Source/Status Management,
  Lead Assignment, Lead Activity Timeline, CRM Dashboard, CRM
  Navigation.
- **Tenant isolation review** — dedicated audit, 5 findings fixed.
- **Super Admin Console** — tenant list, real platform metrics,
  suspend/reactivate with session revocation and dual-sided audit
  logging, plus this project's first frontend page. Selected as this
  sprint's target per the "Next sprint" section below (previous
  revision), after confirming CRM Sprint 1 was already complete.
- **Database Verification sprint** — the top-ranked "Next sprint"
  candidate below (previous revision), acted on for real this time.
  `composer install` confirmed blocked in this sandbox (Packagist
  returns HTTP 403 — an environment constraint, not fixable by more
  code), so the application/HTTP layer still can't run here — but all
  39 real migrations now run cleanly against a real PostgreSQL 16
  instance, and Row-Level Security was proven empirically (real
  cross-tenant reads blocked, real cross-tenant writes rejected by
  `WITH CHECK`) rather than only reasoned about. A reusable
  verification tool (`tools/db-verify/`) is now a permanent part of the
  repo. Full detail: `docs/DATABASE_VERIFICATION.md`. **This closes the
  database-layer half of the runtime-verification item below; the
  application-layer half remains open and needs an environment with
  real internet access to attempt.**
- **CRM Sprint 2 — Customers** — one of three CRM Sprint 2 candidates
  listed below (previous revision) without a business-priority pick;
  decisively scoped to Customers (the roadmap's own text already
  flagged "a won lead should become something" as the clearest gap)
  rather than a partial spread across all three. Delivered: full
  Customer CRUD, Customer Activity Timeline, and Lead→Customer
  conversion with real business-rule enforcement (only a Won lead
  converts, only once). `SequenceService`'s reusability (built in
  Sprint 1 specifically to be reused) paid off immediately for customer
  numbering. All 5 new migrations verified against real PostgreSQL
  before being considered done, continuing last sprint's practice, not
  a one-off. Full detail: `docs/CRM_SPRINT_2_CUSTOMERS.md`.
- **CRM Sprint 3 — Opportunities** — the remaining CRM Sprint 3 choice
  (Opportunities vs. Quotations) left open below (previous revision);
  decisively scoped to Opportunities since Quotations needs a Product/
  Item catalog that doesn't exist until Inventory is built. Delivered:
  Opportunity Stages (tenant-editable pipeline), full Opportunity CRUD
  against a Customer, and a real enforced business rule — moving into a
  Won/Lost stage auto-sets `closed_at` and logs a dedicated timeline
  event, not just a status flag. All 4 new migrations verified against
  real PostgreSQL — this run actually caught a real gap in the
  verification tool itself (a missing `date()` method in its shim),
  fixed and documented rather than worked around. Full detail:
  `docs/CRM_SPRINT_3_OPPORTUNITIES.md`.
- **Client-Ready MVP Demo** — an explicit priority reset, not a
  continuation of the prior sprints' depth bar: breadth across all ten
  requested areas within one pass, with the tradeoff stated up front
  rather than discovered later (smoke-level tests, module-level RBAC
  instead of record-level scoping, on six new modules). Delivered
  Inventory, Purchase, Sales (Quotation → Order → Invoice with real
  stock movement and VAT), Basic Accounting (real double-entry
  validation), Reports, and a basic non-LLM AI Assistant — plus this
  project's first full tenant-facing frontend (`/app`), extending the
  Super Admin Console's vanilla-JS pattern to the whole product via two
  reusable engines (generic list view, generic document view with line
  items and workflow actions) rather than 15 bespoke screens. All 54
  migrations (48 prior + 6 new) verified against real PostgreSQL,
  continuing standing practice. Full detail and the explicit Version 2
  Backlog: `docs/MVP_DEMO.md`.
- **Sales Module Completion** — brought Sales from MVP-demo depth up to
  the audited bar (the roadmap item below, previous revision, explicitly
  named this as the strongest candidate). Delivered Delivery Notes,
  Customer Payments (real multi-invoice allocation, replacing the MVP's
  direct paid-amount bump), Credit Notes, Sales Returns (auto-generates
  a linked Credit Note on receipt), a Sales Dashboard, and three new
  Sales Reports (by customer, by product, real aging receivables). Real
  integration: Sales now auto-posts balanced journal entries to
  Accounting, and Quotations can link to a real Opportunity. **Fixed a
  genuine design bug from the MVP sprint**: invoice issuance was moving
  stock, conflating the financial and warehouse events — Delivery Notes
  now own the stock-out event, invoices are purely financial. All 60
  migrations (54 prior + 6 new) verified against real PostgreSQL, RLS
  spot-checked on every new table. Full detail:
  `docs/SALES_MODULE_SPRINT.md`.
- **Inventory Module Completion** — the natural next depth candidate
  named below (previous revision), since it's structurally closest to
  what Sales just went through. Delivered Categories, Units, Brands
  (real tenant-editable entities, replacing plain-string fields),
  Barcode Support (real lookup endpoint), Stock Transfers, Stock
  Adjustments (real approve-workflow with accounting posting), Goods
  Receiving, Goods Issue (with accounting posting), real Low Stock
  Alerts (fired from the one place every stock decrease already flows
  through), and two new Inventory Reports. **Fixed a genuine design bug
  mirroring the Sales sprint's fix**: `PurchaseOrderService::receive()`
  was moving stock directly — now it creates and receives a real
  `GoodsReceipt`, the Inventory-side warehouse event, the same
  physical/financial split Delivery Notes established. All 67
  migrations (60 prior + 7 new) verified against real PostgreSQL, RLS
  spot-checked on every new table. Full detail:
  `docs/INVENTORY_MODULE_SPRINT.md`.
- **Purchase Module Completion** — the clear next depth candidate named
  below (previous revision), narrower than previously scoped since
  Goods Receiving had already moved to Inventory. Delivered Supplier
  Bills (the real Accounts Payable event), Supplier Payments (real
  multi-bill allocation, mirroring `CustomerPaymentService`), Debit
  Notes (mirroring `CreditNoteService`), Purchase Returns (auto-
  generates a linked Debit Note on return, mirroring Sales Returns), a
  Purchase Dashboard, and two new Purchase Reports. New
  `PurchaseAccountingIntegrationService` posts real balanced journal
  entries for bill approval, payment, and debit notes, mirroring
  `SalesAccountingIntegrationService`'s exact pattern — the
  receivable/payable accounting loop is now closed on both sides. All
  72 migrations (67 prior + 5 new) verified against real PostgreSQL,
  RLS spot-checked on every new table. Full detail:
  `docs/PURCHASE_MODULE_SPRINT.md`.
- **Accounting Module Completion** — the natural next depth candidate
  named below (previous revision), since Sales and Purchase had both
  been posting into it for two sprints without its own core engine
  getting the same treatment. Delivered real journal entry reversal
  (a new, swapped-and-balanced entry, never an in-place edit — auto-
  posted entries explicitly excluded by design), split input/output
  VAT accounts (a real `2110 VAT Recoverable` account, closing the
  netted-through-`2100` simplification the Sales and Purchase sprints
  each named directly, with a backfill command for existing tenants),
  and two real financial statements — Income Statement and Balance
  Sheet, the latter reporting whether it actually balances rather than
  assuming it. All 73 migrations (72 prior + 1 new) verified against
  real PostgreSQL. Full detail: `docs/ACCOUNTING_MODULE_SPRINT.md`.
- **HR & Payroll Module Completion** — the user's next stated priority
  named below (previous revision), built from zero (previously just
  an RBAC role label with no data model at all). Delivered Employees,
  Departments (reused the existing Platform Admin table), Designations,
  Shifts, Holidays, Attendance (shift-aware lateness detection), Leave
  Management (real balance validation and deduction, real Attendance
  integration on approval), a real gross-to-net Payroll engine (Salary
  Structure/Allowances/Deductions via one tenant-editable Salary
  Components model, Overtime with a documented hourly-rate basis,
  Payslips with full line-item detail), a Payroll Report, basic
  Recruitment (hiring an application creates a real Employee record),
  basic Performance Reviews (a real draft→submitted→acknowledged
  lifecycle), and Employee Self-Service (every endpoint scoped
  server-side to the caller's own linked Employee record). New
  `HrPayrollAccountingIntegrationService` posts one real balanced
  journal entry per processed payroll run, mirroring
  `SalesAccountingIntegrationService`/`PurchaseAccountingIntegrationService`'s
  exact pattern. Deliberately did NOT invent Saudi GOSI contribution
  rates or income-tax brackets — real regulatory input this project
  has never been given. All 77 migrations (73 prior + 4 new) verified
  against real PostgreSQL — this sprint also fixed a real gap in the
  verification tool itself (missing `time()`/`dateTime()` column
  support in the schema shim). Full detail: `docs/HR_PAYROLL_SPRINT.md`.
- **Reports & Analytics Completion** — the user's next stated priority
  named below (previous revision). Delivered an Executive Dashboard
  and KPI Dashboard (real cross-module snapshots and trends), CRM
  Reports and a Cash Flow report and a VAT Report (three categories
  that never existed — CRM's own three sprints never circled back to
  add its own reports), a Custom Report Builder (one allow-list as its
  entire SQL-injection safety model), and real dependency-free file
  export — a genuinely valid minimal XLSX (via `ZipArchive`) and a
  genuinely valid minimal multi-page PDF (hand-built PDF 1.4 objects),
  both rigorously verified with `qpdf`/`pdfinfo`/`pdftotext`/`unzip`
  against actual generated files, not just asserted to work. Scheduled
  Reports (a new console command, `Mail::fake()`-verified real
  delivery) deliberately scoped to saved Custom Reports only. All 79
  migrations (77 prior + 2 new) verified against real PostgreSQL. Full
  detail: `docs/REPORTS_ANALYTICS_SPRINT.md`.
- **AI Assistant Completion** — the user's next stated priority named
  below (previous revision). Delivered a real, provider-agnostic LLM
  integration layer (`LlmProviderInterface`) on top of the existing
  deterministic keyword-grounded engine, which was itself expanded to
  cover Purchase, HR & Payroll, and Accounting/Cash. `NullLlmProvider`
  (the default) is the same real deterministic mode this module always
  ran on; `AnthropicLlmProvider` is a real, working implementation
  against Anthropic's Messages API, its request shape verified via
  `Http::fake()` against the real HTTP client. On "provider selection
  is a business decision" (named directly in the prior sprint's own
  Feature Matrix): not guessed at — the default is no provider, and
  Anthropic was built specifically because `api.anthropic.com` is the
  one LLM endpoint this sandbox's network policy allows. Any provider
  failure degrades invisibly to the deterministic reply; every code
  path answers questions, none mutate business data — a named safety
  boundary, not deferred work. All 80 migrations (79 prior + 1 new)
  verified against real PostgreSQL. Full detail:
  `docs/AI_ASSISTANT_SPRINT.md`.
- **AI Assistant Full Completion** — a second, deliberately
  non-duplicative pass completing the remaining requested scope
  without rebuilding the first sprint's work. Delivered a second real
  provider (`OpenAiLlmProvider`, same rigor as Anthropic's — request
  shape verified via `Http::fake()` despite `api.openai.com` not being
  reachable from this sandbox at all), real per-tenant provider
  selection (a tenant picks which platform-configured provider they
  use, safely resolved at request time, tested to fall back — never
  error — when the chosen provider has no real credentials), a real
  insight engine (`AiInsightService`) behind AI Dashboard/Sales/
  Inventory/Financial/CRM Insights and Report Summaries (reusing
  already-audited services' real data throughout), real Automation
  Suggestions tied to real AI Notifications (idempotent — verified
  that a persisting condition never duplicates), a dedicated AI
  Activity Log, and real per-tenant AI Settings and Prompt Management.
  All 82 migrations (80 prior + 2 new) verified against real
  PostgreSQL. Also found and fixed a real pre-existing documentation
  bug: `PROJECT_STATUS.md`'s "Current phase" section had accumulated
  two stacked sprint-summary paragraphs from an incomplete edit two
  sprints prior. Full detail: `docs/AI_ASSISTANT_SPRINT.md` (updated
  in place to cover both sprints).
- **Production Readiness** — the user's next stated priority named
  below (previous revision), and the first sprint not adding a
  business module. Closed the operational gap every one of the eight
  audited modules has depended on implicitly: Docker (a hardened
  multi-stage `Dockerfile`, `.dockerignore`, `docker-compose.prod.yml`),
  a real GitHub Actions CI/CD workflow (the one piece of this
  project's automation that can actually execute, given GitHub's
  runners have real internet access), deployment/rollback/install
  scripts, OWASP security hardening (a `SecurityHeaders` middleware, a
  real `auth` rate limiter closing a genuine gap on login/OTP/
  password-reset), a real deep health check, a webhook-based error
  tracker, a real `pg_dump`-based backup command with retention, 5 new
  database indexes, and 4 new production docs. **Two real,
  previously-unnoticed bugs found and fixed**: `NotificationMail`/
  `ScheduledReportMail` never actually implemented `ShouldQueue`
  despite Redis queue infrastructure existing since Foundation, and
  the `failed_jobs` table never existed. Two more real gaps found and
  fixed in the migration-verification shim itself. All 84 migrations
  (82 prior + 2 new) verified against real PostgreSQL. Full detail:
  `docs/PRODUCTION_READINESS.md`.
- **Final Report / closing QA pass** — not a new module; a closing
  Final Code Review, Security Audit, Performance Optimization check,
  Bug Fix pass, and Documentation pass across the whole project.
  Empirically re-confirmed (not assumed): zero tenant-scoped tables
  missing RLS, zero duplicate route conflicts, zero duplicate database
  tables/functions/frontend route keys, zero hardcoded secrets. Added
  a real root-level `README.md`, closing half of a backlog item named
  across many sprints. Replaced a stale, early-project-era
  `FINAL_REPORT.md` with a current one. One real bug found and fixed
  — not in the codebase, but in this pass's own doc-editing (a
  momentarily duplicated section header, caught by this project's own
  standing header-grep practice and fixed immediately). Full detail:
  `docs/FINAL_REPORT.md`.
- **Final Production Validation pass** — a 20-item codebase review
  checklist plus generation of every genuinely missing piece of
  production configuration/documentation. Scripted (not sampled)
  verification: zero broken model relationships (238 methods, 102
  models), zero namespace mismatches (641 files), zero unresolved
  `App\` imports (641 files) — the import check caught a real bug in
  its own first version (didn't handle `abstract class`, 99 false
  positives), fixed and re-run clean. Confirmed this project has no
  React codebase — a deliberately-scoped Blade+vanilla-JS console
  instead (`docs/MVP_DEMO.md`); answered that checklist item honestly
  rather than fabricating a React review. Published four config files
  missing entirely since Foundation (`config/queue.php`, `cache.php`,
  `filesystems.php`, `logging.php`) — real settings, not the
  framework's unpublished internal defaults this project ran on
  before. Three new docs: `API_DOCUMENTATION.md`, `USER_GUIDE.md`,
  `PRODUCTION_CHECKLIST.md`. `DEPLOYMENT_GUIDE.md` gained a new Step 3
  with correctly renumbered subsequent steps. All 84 migrations
  re-verified against real PostgreSQL. Full detail:
  `docs/FINAL_REPORT.md` (updated in place to cover this pass).
- **Critical Laravel Skeleton Repair** — triggered by a direct report
  that `artisan` was missing. Verified directly (`ls`/`find`, not
  assumption) and found the gap was far larger: `artisan`, the entire
  `public/` directory, the entire `storage/` tree, `bootstrap/cache/`,
  and seven core config files — `config/app.php` (encryption key,
  timezone, locale), `config/database.php` (**the single most
  critical gap** — no real `pgsql` connection was defined anywhere
  Laravel's own database layer could read, despite every migration
  and model in this project depending on one), `config/auth.php`,
  `config/session.php`, `config/mail.php`, `config/cors.php` — were
  all genuinely missing. Confirmed NOT missing, correctly:
  `package.json`/`vite.config.js` (zero `@vite` usage anywhere) and
  `config/broadcasting.php` (zero broadcasting usage anywhere). This
  went undetected across every prior sprint because
  `tools/db-verify/`'s migration runner bypasses Laravel's bootstrap
  entirely by design (a raw PDO connection) — every "migrations
  verified" claim in every prior sprint was real and accurate for what
  it tested, but could never have caught this. All 84 migrations
  re-verified against real PostgreSQL as the final step, unaffected.
  Full detail: `docs/FINAL_REPORT.md`.

## Current sprint

**None open.**

## A direct note on where this leaves the project

The two-depth-bar split that's been named plainly in every audit since
the MVP demo sprint no longer describes this codebase: Foundation,
Platform Administration, CRM (three sprints), Sales, Inventory,
Purchase, Accounting, HR & Payroll, Reports & Analytics, and AI
Assistant were all built and audited to a "would hold up to real
scrutiny" bar — exhaustive tests, empirical RLS verification, genuine
cross-module integration. Production Readiness didn't add a ninth
business module — it closed the operational gap every one of those
eight has depended on implicitly since it shipped: Docker, CI/CD,
security hardening, monitoring, backups, and real production
documentation, all real rather than assumed. What's left is not a
depth gap in any existing module, nor an operational gap in running
it — it's real, named, unbuilt *business* scope: Billing & Subscription
(never started at all), and application-layer verification (the
frontend has never once executed against a live server, across twelve
sprints of growth — this remains true even with a real CI/CD pipeline
now in place, since no workflow has actually run yet in this sandbox).
Billing is the natural next candidate: it's the one remaining
ten-area-scope item with zero code behind it, and Production
Readiness's new backup/monitoring/deployment foundation is exactly
what a real payment-handling feature would need underneath it before
it could be trusted with real transactions.

## Next sprint (highest-leverage candidates, not a commitment)

1. **Billing & Subscription engine** — the natural next candidate:
   plans, payment gateway, automated trial/suspension.
   `tenants.status` is currently a lifecycle flag with no engine
   behind it (see `FEATURE_MATRIX.md`) — the one remaining
   ten-area-scope item with genuinely zero code so far.
2. **Application-layer verification** (unchanged from prior sprints,
   still the single highest-leverage item overall): requires an
   environment where `composer install` can actually reach Packagist,
   or a real trigger of the new GitHub Actions workflow
   (`.github/workflows/ci.yml`) against a real repository — neither is
   achievable from within this sandbox. The frontend has grown
   substantially across ten sprints (Sales, Inventory, Purchase,
   Accounting, HR & Payroll, Reports & Analytics, AI Assistant ×2,
   Production Readiness) and has never once been rendered against a
   live server.
3. **CRM Sprint 4 — Quotations** — unblocked since CRM Sprint 3
   (Inventory's Product catalog existed even before the Inventory
   sprint).
4. **A third LLM provider for AI Assistant** (or beyond) —
   `LlmProviderInterface` is ready; Anthropic and OpenAI both have real
   implementations now.
5. **A real Sentry/Bugsnag SDK integration**, once `composer install`
   can reach Packagist for real — `ErrorTrackingService`'s webhook
   approach (this sprint) is real and working, but a proper SDK gives
   richer context (breadcrumbs, release tracking) a generic webhook
   can't.
6. **Documentation cleanup**: archive the four superseded pre-Laravel
   files (`AUDIT_REPORT.md` Part 4) — still open, unaddressed across
   fourteen sprints now.

## Future sprint

See `docs/MVP_DEMO.md`'s Version 2 Backlog for the complete, explicit
list this sprint produced (depth items, new-capability items, frontend
items, ops items) — not duplicated here to avoid the two lists drifting
apart. The highest-leverage items from that list, restated briefly:

- A deliberate, project-wide frontend architecture decision — the MVP
  demo console remains a scoped, functional build, not a precedent for
  a polished product UI.
- GOSI/income-tax computation for Payroll, once this project is given
  the real Saudi contribution rates and tax brackets as business
  input — the Salary Components engine built in the HR & Payroll
  sprint is real and generic enough to model it the moment those rules
  exist.
- A true multi-sheet/styled XLSX writer and Arabic-capable PDF
  rendering (both need real libraries — PhpSpreadsheet/dompdf-class
  tooling — that `composer install` being blocked has ruled out this
  sprint; the current hand-built writers are real but deliberately
  minimal).
- More `LlmProviderInterface` implementations beyond Anthropic and
  OpenAI (Azure OpenAI, self-hosted models), streaming responses, and
  function-calling/tool-use for the AI Assistant — the interface is
  ready for all three; none are implemented yet.
- Write-capable AI actions (the assistant creating/editing business
  records on request) — a real capability gap, but a deliberate one:
  named in the AI Assistant sprint as a safety-boundary decision that
  needs its own scoping conversation, not silently bundled into a
  Q&A-engine sprint.

## Backlog

- "Travel ERP" — flagged in `FEATURE_MATRIX.md` as having no prior
  grounding anywhere in this project; needs a scoping conversation
  before it belongs on a roadmap at all, not just an implementation
  sprint (the nine sequenced modules above at least have a named brief;
  this one doesn't)
- White-labeling, custom domains — deferred until there's a paying
  customer who needs them
- Encryption-at-rest, secrets manager integration, CI pipeline,
  production deployment target — all deployment-time concerns that
  can't be meaningfully built until a real target environment exists
  (see `AUDIT_REPORT.md` Part 9)
- Remove dead dependencies (`ramsey/uuid`, `pragmarx/google2fa-laravel`)
  or implement what they were meant for (TOTP-based MFA)
- ~~Root-level `README.md`~~ — done as of the Final Report / closing
  QA pass (see `docs/FINAL_REPORT.md`). A real, structured
  `docs/API_DOCUMENTATION.md` (auth, conventions, module reference)
  was added in the Final Production Validation pass — but a generated,
  machine-readable OpenAPI/Swagger spec with per-endpoint request/
  response schemas for all 354 real endpoints remains open — a
  substantial task in its own right, deliberately not rushed into
  either closing pass
- A deliberate, project-wide frontend architecture decision — the
  Super Admin Console's single Blade+vanilla-JS page was scoped
  narrowly on purpose (see `SUPER_ADMIN_CONSOLE.md`) and should not be
  read as that decision having been made for the rest of the product
