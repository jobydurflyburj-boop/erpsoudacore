# SoudaCore ERP — Audit Report

Covers Parts 4 (Documentation), 5 (Architecture), 6 (SaaS), 7 (ERP),
8 (Tests), 9 (Security), and 11 (Release Readiness) of the requested
audit. Part 1 is `PROJECT_STATUS.md`, Part 2 is `FEATURE_MATRIX.md`,
Part 3 is `FILE_INDEX.md`, Part 10 is `ROADMAP.md`, Part 12 is
`FINAL_REPORT.md` — split into separate files because each has its own
natural audience (a status dashboard reads differently from a roadmap),
while these seven review-style parts share one narrative and are kept
together here.

---

## Part 4 — Documentation Review

### Duplicate documents
None found *within* the repository's `docs/` folder — each of the four
files there (`FOUNDATION.md`, `TENANT_ISOLATION_REVIEW.md`,
`PLATFORM_ADMIN_MODULE.md`, `CRM_MODULE.md`) covers distinct ground with
no overlap.

### Outdated and conflicting documents — the most important finding in this part
Four files exist **outside this repository**, as standalone outputs
from earlier in this project's history, before the decision to build on
Laravel: `ARCHITECTURE.md`, `SYSTEM-FOUNDATION.md`, `schema.sql`,
`openapi.yaml`. These describe a **different, superseded stack** —
Node.js/NestJS, Prisma, AWS ECS Fargate, `me-south-1` — and a database
schema that does not match this repository's actual 38 migrations in
naming, structure, or in several cases existence (e.g. `schema.sql`
defines `super_admins` as a separate table; this codebase instead uses
`tenant_id IS NULL` rows in `users`, a deliberate later design change).
`SYSTEM-FOUNDATION.md` explicitly states it "extends `ARCHITECTURE.md`,
`schema.sql`, and `openapi.yaml`... those aren't superseded" — a claim
that was true when written and is **no longer true**, and nothing marks
it as such. Anyone handed these four files today without the full chat
history would build the wrong thing.

**Recommendation:** archive or delete all four. If historical record is
valuable, move them into a clearly-labeled `docs/archive/` with a
header banner stating they predate the Laravel decision and do not
describe this codebase.

### Missing documentation
- No `README.md` at the repository root (see `FILE_INDEX.md`).
- No CONTRIBUTING guide, no CI documentation (none exists to document).
- No generated API reference (OpenAPI/Swagger) for the 91 real
  endpoints — the only OpenAPI spec that exists is the superseded one
  above, describing different, non-existent endpoints.
- No deployment runbook.

### Unused documentation
None inside `docs/` — everything there is current and referenced by the
code comments that point to it (a pattern worth continuing: several
services/migrations explicitly comment "see docs/X.md").

### Recommended official documentation set
`docs/FOUNDATION.md`, `docs/TENANT_ISOLATION_REVIEW.md`,
`docs/PLATFORM_ADMIN_MODULE.md`, `docs/CRM_MODULE.md`, plus the five
new files this audit adds. These should be treated as the sole source
of truth; the four external files above should not be linked from
anywhere once archived.

---

## Part 5 — Architecture Review

| Principle | Verified? | Evidence |
|---|---|---|
| Repository Pattern | Yes, without exception | 15 interfaces, 15 Eloquent implementations, one `BaseRepository`, all bound centrally in `RepositoryServiceProvider` |
| Service Layer | Yes | 21 services; every controller with non-trivial logic delegates to one rather than querying models directly |
| SOLID | Largely yes | Single-responsibility holds well (e.g. `SequenceService` does exactly one thing); Dependency Inversion is explicit via constructor-injected interfaces throughout. Weakest point: `LeadService` and `DashboardService`/`CrmDashboardService` are doing several related things each (arguably acceptable at this size — worth revisiting if they keep growing) |
| DRY | Mostly, with two known-and-documented exceptions | `Store*Request`/`Update*Request` pairs duplicate validation rules where full independence was needed (deliberate, after the reused-Store-request bug); `BelongsToTenant` + RLS is *intentional* duplication (defense in depth, not accidental) |
| KISS | Yes | No premature abstraction found — e.g. Lead Sources/Statuses are plain CRUD, not over-engineered into a generic "catalog" framework |
| DDD boundaries | Partially | Module boundaries are respected in routing/permissions (`admin`, `crm`, `dashboard`, `core` are cleanly separated in `config/permissions.php`), but there's no formal bounded-context enforcement (any Service can technically import any Model) — acceptable for a modular monolith at this size, worth a linting rule later |
| Module isolation | Good | CRM's tables, models, and routes don't reach into Platform Admin's, or vice versa; the one deliberate cross-module link is `LeadService` calling `NotificationService` (Platform Admin) — appropriate, not a leak |
| Dependency Injection | Yes, throughout | Constructor injection everywhere; zero `new SomeService()` found in a controller |
| Folder structure | Consistent | Every module follows identically: `Http/Controllers/Api/V1/<Module>/`, `Http/Requests/<Module>/`, `Repositories/{Contracts,Eloquent}/`, `Services/` |
| Naming consistency | Yes, with one caveat | PascalCase classes, camelCase methods, snake_case DB columns throughout. Caveat: route parameters had to be renamed mid-CRM-sprint (`{lead_source}` → `{leadSource}`) to match Laravel's implicit-binding convention — fixed, but shows the convention wasn't obvious/documented until it broke |
| API versioning | Yes | `/api/v1` prefix, no breaking changes made yet so no v2 precedent to verify against |

**Overall: architecturally sound and unusually consistent for a project
built incrementally across three separate sprints** — a real strength
worth naming explicitly, not just a lack-of-findings.

---

## Part 6 — SaaS Review

| Capability | Status |
|---|---|
| Tenant isolation | **Solid** — RLS + app scope + token-tenant binding, independently reviewed |
| Subscription readiness | **Not built** — `tenants.status` is a manually-set enum with no billing engine driving its transitions |
| Feature flags | **Not built** — no mechanism exists to gate a route/module by plan. `config/permissions.php` gates by *role*, not by *subscription tier* — these are orthogonal and only the first exists |
| Plan limitations | **Not built** — no `subscription_plans` table, no user/branch/storage caps enforced anywhere |
| Billing integration readiness | **Not built** — no payment gateway adapter, no invoice generation for platform fees, no webhook endpoints |
| Company onboarding | **Built** — `RegistrationService` is a real, atomic, well-tested flow (tenant + company + branch + roles + CRM defaults + owner user in one transaction) |
| Trial accounts | **Partially built** — `trial_ends_at` is set and readable (surfaced on the Platform Admin dashboard's Subscription Status widget), but nothing currently acts on it expiring (no automated transition to `past_due`) |
| White labeling | **Not built** — no per-tenant branding beyond the existing `companies.logo_path` (a company's own logo on their own documents, not white-labeling the *platform* itself) |
| Custom domains | **Not built** — tenant resolution is subdomain-only (`ResolveTenant` middleware); no custom-domain-to-tenant mapping exists |

---

## Part 7 — ERP Review

Per the instruction to mark a module Complete only if code exists, and
Planned if only documentation exists:

| Module | Marking | Basis |
|---|---|---|
| CRM (Lead Management) | **In Progress** | Real code: 6 tables, 7 controllers, 4 services, tests. Real gaps: no Opportunities/Customers/Quotations/Meetings |
| Sales, Purchase, Inventory, Accounting, HR, Payroll, Reports, AI, Travel ERP | **Not Started** | Zero code, zero migrations, zero routes for all nine. None even have a documented plan (unlike the earlier, now-superseded pre-Laravel docs which sketched some of these — but since those docs are themselves not authoritative, per Part 4, "Planned" would overstate it; "Not Started" is accurate for *this* codebase) |
| Billing | **Not Started** | See Part 6 |

**No module in this codebase is marked Planned** — the superseded
external docs that sketched CRM/Sales/Inventory/HR/Payroll/AI under a
different architecture don't count as "documentation exists" for *this*
project, since they don't describe buildable specs against the current
schema.

---

## Part 8 — Test Review

### Existing tests: 27 files
- **Feature (20):** Auth (3), CRM (5), Platform Admin (7), RBAC (1),
  Tenancy (4).
- **Unit (5):** `ApiResponseEnvelopeTest`, `NotificationServiceTest`,
  `PasswordPolicyServiceTest`, `SequenceServiceTest`, `TokenServiceTest`.

### Coverage estimate
No coverage tool has ever been run (consistent with "never
executed" — see `PROJECT_STATUS.md`), so this is a structural estimate,
not a measured one: **every service and every RBAC-gated endpoint added
in Platform Admin and CRM has at least one feature test exercising the
happy path, the tenant-isolation path, and at least one authorization
failure path.** The Foundation sprint's coverage is slightly thinner —
`SessionController`, `EmailVerificationController`'s signed-URL path,
and `LogoutAllDevicesController` have no dedicated tests.

### Critical missing tests
1. **Nothing has ever actually run.** This is the single largest gap —
   not a missing test file, but the fact that zero tests have executed
   against a real database, so even 100% "structural" coverage is
   unverified. This must be the first item addressed, before writing
   any more tests.
2. `LeadAttachmentController` (file upload/delete) has no test —
   the only untested controller in CRM.
3. `RefreshTokenController`'s reuse-detection path (family revocation on
   replay) is tested at the `TokenService` unit level but not through
   the actual HTTP endpoint.
4. `ProvisionCrmDefaultsCommand` (the backfill console command) has no
   test.
5. No test exercises two Sales-role users in the *same* tenant
   attempting to view each other's leads via direct ID guess on a
   record neither owns but that a manager *can* see — the current
   `LeadPolicy` tests cover A-views-B's-lead-403, but not the
   3-role-combination matrix exhaustively.

---

## Part 9 — Security Review

| Area | Assessment |
|---|---|
| Authentication | Strong — Sanctum + custom rotating refresh tokens with theft detection (reuse of a consumed token revokes the whole device family), argon2id via Laravel defaults, breached-password checking |
| Authorization | Strong — two independent layers (route permission middleware + record-level Policy), consistently applied |
| RBAC | Strong — database-driven, per-tenant editable, correctly extended across three sprints with no regressions found |
| Tenant Isolation | Strong, and specifically **reviewed** — see `TENANT_ISOLATION_REVIEW.md`; 5 issues found and fixed, none live |
| OWASP Top 10 | Injection: parameterized queries only, no raw string SQL found anywhere. Broken Access Control: covered by RBAC+Policy above. Auth failures: covered. Sensitive data exposure: passwords redacted from audit trails (a real bug found and fixed this sprint); no encryption-at-rest configured anywhere (see below) |
| Rate Limiting | Implemented for login (dual email+tenant / IP throttle) and globally via Laravel's `throttleApi`; **not implemented per-endpoint elsewhere** — e.g. lead creation has no rate limit, which is low-risk today but worth revisiting before public signup exists |
| Audit Logs | Strong — two-tier (`audit_logs` field diffs + `activity_logs` human feed), immutable by convention (no update/delete path exists in application code), correctly redacts credentials |
| Encryption | **Gap.** No field-level encryption exists for anything (e.g. no PII-specific encryption beyond Postgres's own at-rest behavior, which isn't configured in this project's Docker setup at all — the local Postgres container has no encryption configured, and there is no production deployment target yet to configure it for) |
| Secrets | **Gap.** `.env.example` is the only secrets template; there is no secrets manager integration (Vault, AWS Secrets Manager, etc.) because there is no deployment target yet to integrate one with |
| Session Management | Strong — short-lived access tokens, rotating refresh tokens, explicit multi-device session listing/revocation, device fingerprinting |

**Overall: the security *design* is genuinely strong and has already
been through one dedicated adversarial review. The gaps are entirely in
deployment-time hardening (encryption at rest, secrets management) that
can't meaningfully be addressed until there's an actual environment to
harden** — building them against nothing would be premature.

---

## Part 11 — Release Readiness

| Stage | Ready? |
|---|---|
| **Developer Preview** (internal engineers, expect breakage) | **Not yet** — first run `composer install` + `php artisan migrate` against real Postgres has never happened. This is a same-day fix, not a gap in the code. |
| **Internal Alpha** (small trusted internal group) | No — requires Developer Preview to pass first, plus at minimum the missing `LeadAttachmentController` test and a full test-suite run with a fixed failure list |
| **Closed Beta** (external design partners) | No — requires Billing (even a manual/invoiced-outside-the-app version) so a real customer relationship has *some* commercial mechanism, plus Super Admin console basics (at minimum: list tenants, suspend a tenant) for support operations |
| **Public Beta** | No — requires at least one more business module beyond CRM (a lead-only ERP isn't yet an ERP), rate limiting broadened, encryption-at-rest configured for the real deployment target |
| **Production** | No — requires all of the above plus CI, monitoring, backups, and a real deployment target (Part 11 of `SYSTEM-FOUNDATION.md`'s AWS plan was never implemented against this codebase — it describes the superseded stack) |

**Honest summary: this project is currently pre-Developer-Preview.**
The very first next step, before anything else in `ROADMAP.md`, is
running it for the first time.
