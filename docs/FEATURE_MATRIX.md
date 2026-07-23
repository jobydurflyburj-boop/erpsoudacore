# SoudaCore ERP — Feature Matrix

Every count below is computed directly from the repository (file
listings, `grep`, route dumps) during this audit — not estimated.
"Files involved" counts PHP files only (migrations counted separately).

**Critical finding, fixed after this audit's prior revision:** the
real Laravel 12 framework skeleton (`artisan`, `public/index.php`,
`public/.htaccess`, `storage/` tree, `bootstrap/cache/`, and
`config/app.php`/`database.php`/`auth.php`/`session.php`/`mail.php`/
`cors.php`) was missing from this repository entirely — every module
below was, and remains, correct at the `app/` code level, but the
application itself could not have booted without these. See
`docs/PROJECT_STATUS.md`'s "Current phase" and `docs/FINAL_REPORT.md`
for the full account of what was found and why it went undetected for
this long.

**Cross-cutting update:** as of the Client-Ready MVP Demo sprint, six
modules below (Sales, Purchase, Inventory, Accounting, Reports, AI) used
a deliberately different depth bar than Foundation/Platform Admin/CRM —
smoke-level tests rather than exhaustive coverage, module-level RBAC
rather than record-level scoping. All six have since been brought up to
the exhaustive, audited bar the earlier three (Foundation/Platform
Admin/CRM) carry — **AI**, the last of the six, reached it this sprint.
Every module that started at the reduced MVP bar is now at the
exhaustive one; stated explicitly per module below and in full in
`docs/MVP_DEMO.md`. **HR & Payroll** was never part of that original
six — it didn't exist at all until its own sprint — but was built
directly to the exhaustive audited bar from the start, the same as
Reports & Analytics and AI Assistant were in their own sprints.
Database-layer verification (`docs/DATABASE_VERIFICATION.md`) still
applies to every module — all
their migrations were run against real PostgreSQL before
being considered done, same standing practice as every module before
them. **A Final Report / closing QA pass**, followed by a **Final
Production Validation pass** (see `docs/FINAL_REPORT.md`, updated in
place to cover both), re-verified this empirically across the whole
project directly rather than by carrying prior sprints' claims
forward — RLS coverage, route/table/function duplication, model
relationships, namespaces, imports, and secrets handling were all
re-checked against the actual current files, not assumed. The second
of these two passes also published four previously-missing config
files (`config/queue.php`, `cache.php`, `filesystems.php`,
`logging.php` — see the Production Readiness section below) and added
three new docs (`API_DOCUMENTATION.md`, `USER_GUIDE.md`,
`PRODUCTION_CHECKLIST.md`).

---

## Foundation (Tenancy + Authentication + RBAC)

| | |
|---|---|
| **Status** | Completed |
| **Completion** | 100% of its own defined scope |
| **Files involved** | ~50 (Auth controllers/requests: 21; core models, middleware, services shared with every other module) |
| **Database tables** | `tenants`, `companies`, `branches`, `departments`, `permissions`, `roles`, `role_permissions`, `users`, `user_branches`, `personal_access_tokens`, `refresh_tokens`, `otp_codes`, `password_histories`, `failed_login_attempts`, `user_devices`, `audit_logs`, `activity_logs`, `password_reset_tokens` (18 tables, migrations `2025_01_01_*` + `2025_01_02_*`) |
| **API endpoints** | Register, login, OTP verify, refresh, logout (+ all devices), forgot/reset password, change password, email verification (send+verify), sessions list/revoke, Super Admin login — 17 endpoints |
| **Frontend pages** | None — no frontend exists in this repository at all (see `PROJECT_STATUS.md` and Part 5 folder-structure note) |
| **Services** | `AuthService`, `TokenService`, `RegistrationService`, `PasswordResetService`, `PasswordPolicyService`, `OtpService`, `EmailVerificationService`, `LoginRateLimiter`, `DeviceService`, `ActivityLogService`, `AuditLogService` |
| **Repositories** | `TenantRepository`, `UserRepository` |
| **Controllers** | 12 (`Api/V1/Auth/*`) |
| **Tests** | `RegisterCompanyTest`, `LoginTest`, `OtpLoginTest`, `TokenServiceTest`, `PasswordPolicyServiceTest`, `TenantIsolationTest`, `CrossTenantTokenTest` — 7 files |
| **Missing items** | MFA is OTP-only (email/SMS) — the `pragmarx/google2fa-laravel` dependency is declared in `composer.json` but never used anywhere in `app/` (dead dependency, TOTP was never implemented) |
| **Technical debt** | `ramsey/uuid` and `pragmarx/google2fa-laravel` are both declared dependencies with zero usages in the codebase — remove or implement |

## Platform Administration

| | |
|---|---|
| **Status** | Completed |
| **Completion** | 100% of its own defined scope |
| **Files involved** | ~55 |
| **Database tables** | `company_settings`, `tasks`, `notifications`, `notification_preferences`, `push_device_tokens` (new, migrations `2025_02_01_*`) + extensions to `companies`, `branches`, `departments`, `activity_logs` |
| **API endpoints** | Dashboard, notifications (5), notification preferences (2), push tokens (2), tasks (5, apiResource), admin users/roles/permissions/departments/branches/companies/company-profile/company-settings (~35), audit/activity logs (3) — 45 endpoints |
| **Frontend pages** | None |
| **Services** | `DashboardService`, `NotificationService`, `TaskService`, `UserService`, `RoleService`, `RoleProvisioningService` |
| **Repositories** | `CompanyRepository`, `CompanySettingRepository`, `BranchRepository`, `DepartmentRepository`, `RoleRepository`, `PermissionRepository`, `TaskRepository`, `NotificationRepository` |
| **Controllers** | 12 (`Api/V1/Admin/*`, `Api/V1/Platform/*`) |
| **Tests** | `DashboardTest`, `CompanyProfileTest`, `BranchDepartmentTest`, `UserManagementExtrasTest`, `NotificationCenterTest`, `ActivityLogModuleTest`, `RolePermissionManagementTest`, `PlatformAdminTenantIsolationTest`, `NotificationServiceTest` — 9 files |
| **Missing items** | SMS/WhatsApp/push notification transport are stubbed (`Log::info` placeholders with explicit `TODO(ops)` markers) — real gateway credentials were never available in this environment |
| **Technical debt** | None found beyond the transport TODOs, which are intentionally flagged rather than hidden |

## CRM (Sprint 1 + 2 + 3 — Lead Management + Customers + Opportunities)

| | |
|---|---|
| **Status** | In Progress |
| **Completion** | ~40% of a full CRM module (Lead Management + Customers + conversion + Opportunities pipeline; Quotations, Meetings, CRM Tasks not started). **CRM Reports were added in the Reports & Analytics completion sprint** — leads by source/status, opportunities by stage, a real conversion funnel — documented under Reports & Analytics below rather than duplicated here |
| **Files involved** | 93 (67 after Sprint 2 + 26 new this sprint) |
| **Database tables** | Sprint 1: `sequence_counters`, `lead_sources`, `lead_statuses`, `leads`, `lead_activities`, `lead_attachments` (`2025_03_01_*`). Sprint 2: `customers`, `customer_activities` (`2025_05_01_*`). Sprint 3: `opportunity_stages`, `opportunities`, `opportunity_activities` (`2025_06_01_*`) |
| **API endpoints** | Sprint 1: 22. Sprint 2: 8. Sprint 3: opportunities CRUD + assign (6), opportunity activities (2), opportunity stages CRUD (5) — 13 new. **43 endpoints total** |
| **Frontend pages** | None |
| **Services** | Sprint 1: `LeadService`, `CrmDashboardService`, `CrmProvisioningService`, `SequenceService`. Sprint 2: `CustomerService`, `LeadConversionService`. Sprint 3: `OpportunityService` (stage-change business rules: won/lost auto-sets `closed_at`) |
| **Repositories** | Sprint 1: `LeadRepository`, `LeadSourceRepository`, `LeadStatusRepository`, `LeadActivityRepository`. Sprint 2: `CustomerRepository`, `CustomerActivityRepository`. Sprint 3: `OpportunityRepository`, `OpportunityStageRepository`, `OpportunityActivityRepository` |
| **Controllers** | 12 (`Api/V1/Crm/*`) — 9 after Sprint 2 + `OpportunityController` + `OpportunityActivityController` + `OpportunityStageController` |
| **Policies** | `LeadPolicy`, `CustomerPolicy`, `OpportunityPolicy` (new — third consecutive exact repeat of the same record-level scoping pattern) |
| **Tests** | Sprint 1: 7 files. Sprint 2: 3 files. Sprint 3: `OpportunityManagementTest`, `OpportunityStageManagementTest`, `CrmOpportunityTenantIsolationTest` — 3 new files, 10 new cases |
| **Missing items** | Quotations (needs a Product/Item catalog from Inventory first), Meetings/calendar, CRM-specific Reports |
| **Technical debt** | None found in the module itself — Sprint 3's 4 new migrations were verified against real PostgreSQL immediately (`tools/db-verify/`), including a real cross-tenant RLS write-rejection check, before being considered done. The verification tool's own shim needed a fix (missing `date()` method) — not module technical debt, but noted for transparency in `CRM_SPRINT_3_OPPORTUNITIES.md` |

## Sales

| | |
|---|---|
| **Status** | Completed (audited depth — same bar as CRM's three sprints) |
| **Completion** | Full module: Quotations, Sales Orders, Delivery Notes, Sales Invoices, Customer Payments (multi-invoice allocation), Credit Notes, Sales Returns, a dedicated Sales Dashboard, and three Sales Reports. Real integration with CRM (Opportunity linking), Inventory (Delivery Notes/Returns move real stock), and Accounting (auto-posted, balanced journal entries) |
| **Files involved** | ~75 (14 models, 9 repositories, 9 services, 8 controllers, 16 Form Requests, 15 Resources) |
| **Database tables** | `quotations`, `quotation_items`, `sales_orders`, `sales_order_items`, `sales_invoices`, `sales_invoice_items` (`2025_07_01_000300`); `delivery_notes`, `delivery_note_items`, `customer_payments`, `payment_allocations`, `credit_notes`, `credit_note_items`, `sales_returns`, `sales_return_items` (`2025_08_01_*`); plus `quotations.opportunity_id`, `sales_invoices.credited_amount`, `journal_entries.source_type`/`source_id` |
| **API endpoints** | 43 (up from 18 — added Delivery Notes CRUD + deliver, Payments CRUD + allocate, Credit Notes CRUD + issue, Sales Returns CRUD + receive, Sales Dashboard) |
| **Frontend pages** | Sales Dashboard, Quotations, Sales Orders, Delivery Notes, Invoices, Payments, Credit Notes, Sales Returns — 8 screens, reusing the generic list/document engines plus purpose-built renderers for dashboard, payments, and workflow actions |
| **Services** | `QuotationService`, `SalesOrderService`, `SalesInvoiceService` (now purely financial on `issue()`), `DeliveryNoteService` (the real stock-out event), `CustomerPaymentService` (real multi-invoice allocation), `CreditNoteService`, `SalesReturnService` (auto-generates linked Credit Notes), `SalesDashboardService`, `SalesAccountingIntegrationService` (real journal-entry auto-posting), shared `CalculatesDocumentTotals` trait |
| **Tests** | `SalesModuleIntegrationTest` (5 cases: full Opportunity→Quotation→Order→Delivery→Invoice→Payment→Return→CreditNote flow with real stock/accounting/balance assertions at every step, double-delivery rejection, multi-invoice allocation, credit-note-over-balance rejection), `SalesExtensionTenantIsolationTest` (2 cases), `SalesReportsAndDashboardTest` (1 case) — 8 test cases, integration-depth not smoke-level |
| **Missing items** | Record-level (own-records) scoping, PDF generation, ZATCA e-invoicing, partial delivery of a single Sales Order, payment reversal |
| **Technical debt** | None found — a genuine design bug from the MVP sprint (invoice issuance moving stock, conflating the financial and warehouse events) was found and fixed this sprint, not carried forward. See `SALES_MODULE_SPRINT.md` |

## Purchase

| | |
|---|---|
| **Status** | Completed (audited depth — same bar as CRM, Sales, and Inventory) |
| **Completion** | Full module: Suppliers, Purchase Orders (receiving creates a real Goods Receipt, per the Inventory sprint), Supplier Bills (real Accounts Payable), Supplier Payments (real multi-bill allocation), Debit Notes, Purchase Returns (auto-generates a linked Debit Note), a Purchase Dashboard, and two new Purchase Reports |
| **Files involved** | ~60 (8 models, 6 repositories, 6 services, 6 controllers, 11 Form Requests, 12 Resources) |
| **Database tables** | `suppliers`, `purchase_orders`, `purchase_order_items` (`2025_07_01_000200`); `supplier_bills`, `supplier_bill_items`, `supplier_payments`, `supplier_payment_allocations`, `debit_notes`, `debit_note_items`, `purchase_returns`, `purchase_return_items` (`2025_10_01_*`) |
| **API endpoints** | 30 (up from 9 — added Supplier Bills CRUD + approve + record-payment, bill-from-goods-receipt, Supplier Payments CRUD + allocate, Debit Notes CRUD + issue, Purchase Returns CRUD + return, Purchase Dashboard) |
| **Frontend pages** | Purchase Dashboard, Suppliers, Purchase Orders, Supplier Bills, Supplier Payments, Debit Notes, Purchase Returns — 7 screens, reusing the generic list/document engines and the shared line-item builder across all new document types, plus a "Bill" action added to the Goods Receipts screen linking Purchase and Inventory in the UI |
| **Services** | `PurchaseOrderService` (unchanged since the Inventory sprint), `SupplierBillService` (incl. `createFromGoodsReceipt()`, real double-entry-safe `recalculateStatus()`), `SupplierPaymentService` (real multi-bill allocation, mirrors `CustomerPaymentService`), `DebitNoteService` (mirrors `CreditNoteService`), `PurchaseReturnService` (auto-generates and issues a linked Debit Note on return), `PurchaseAccountingIntegrationService` (real journal-entry auto-posting for bill approval, payment, and debit notes) |
| **Tests** | `PurchaseModuleIntegrationTest` (4 cases, including one full real PO → Goods Receipt → Supplier Bill → Approve → Payment → Return → Debit Note flow with stock/accounting/balance assertions at every step), `PurchaseExtensionTenantIsolationTest` (2 cases), `PurchaseReportsAndDashboardTest` (1 case), plus the prior `PurchaseMvpTest` — integration depth, not smoke-level |
| **Missing items** | Record-level (own-records) scoping, split input/output VAT accounts (currently netted through one account), purchase requisitions/approval workflows, landed cost allocation, partial billing against a single Goods Receipt, supplier payment reversal |
| **Technical debt** | None found — see `PURCHASE_MODULE_SPRINT.md` for the one deliberate simplification (netted VAT accounts) named directly rather than left implicit |

## Inventory

| | |
|---|---|
| **Status** | Completed (audited depth — same bar as CRM and Sales) |
| **Completion** | Full module: Products (with real Categories/Units/Brands/Barcode), Warehouses (full CRUD), Stock Levels/Movements, Stock Transfers, Stock Adjustments (real approve-workflow with accounting posting), Goods Receiving (now the real Purchase-integration event), Goods Issue (with accounting posting), real Low Stock Alerts, and two new Inventory Reports |
| **Files involved** | ~95 (14 models, 11 repositories, 8 services, 11 controllers, 16 Form Requests, 20 Resources) |
| **Database tables** | `products`, `warehouses`, `stock_levels`, `stock_movements` (`2025_07_01_000100`); `product_categories`, `units`, `brands` + taxonomy FK columns on `products` incl. unique `barcode` (`2025_09_01_000100`–`000200`); `stock_transfers`, `stock_transfer_items`, `stock_adjustments`, `stock_adjustment_items`, `goods_receipts`, `goods_receipt_items`, `goods_issues`, `goods_issue_items` (`2025_09_01_000300`–`000600`) |
| **API endpoints** | 41 (up from 8 — added Categories/Units/Brands CRUD, Warehouses show/update/destroy, barcode lookup, low-stock list, Transfers/Adjustments/Goods Receipts/Goods Issues CRUD + workflow actions) |
| **Frontend pages** | Products, Categories, Units, Brands, Warehouses, Stock, Stock Transfers, Stock Adjustments, Goods Receipts, Goods Issues — 10 screens, reusing the generic list engine plus a shared line-item builder across all four new document types |
| **Services** | `InventoryService` (extended: `findByBarcode()`, `lowStockProducts()`, real Low Stock Alert hook fired from the one place every stock decrease flows through), `StockTransferService`, `StockAdjustmentService`, `GoodsReceiptService`, `GoodsIssueService`, `InventoryAccountingIntegrationService` (real journal-entry auto-posting for adjustments and issues) |
| **Tests** | `InventoryModuleIntegrationTest` (4 cases, including one full real Category/Unit/Brand/Barcode → Warehouse → Goods Receipt → Transfer → Adjustment → Issue → Low Stock Alert flow with stock/accounting assertions at every step), `InventoryExtensionTenantIsolationTest` (2 cases), `InventoryReportsTest` (1 case), plus a new case in `PurchaseMvpTest` proving the redesigned receive flow creates a real linked Goods Receipt — 8 cases at integration depth, not smoke-level |
| **Missing items** | Record-level (own-records) scoping, Purchase-side accounting integration (Accounts Payable on Goods Receipt), FIFO/weighted-average costing (single `cost_price` valuation only), batch/lot and serial tracking, barcode label printing, partial goods receiving |
| **Technical debt** | None found — a genuine design bug from the MVP sprint (`PurchaseOrderService::receive()` moving stock directly instead of through a real warehouse-event entity) was found and fixed this sprint, mirroring the exact correction the Sales sprint made for Delivery Notes. See `INVENTORY_MODULE_SPRINT.md` |

## Accounting

| | |
|---|---|
| **Status** | Completed (audited depth, this sprint) |
| **Completion** | Seeded chart of accounts, now 10 standard accounts (added a real `2110 VAT Recoverable` account this sprint, splitting input tax from Sales' `2100 VAT Payable`). Manual journal entries with real double-entry validation, plus **new this sprint:** real reversal (`AccountingService::reverseEntry()` — a new, swapped-and-balanced entry, never an in-place edit; auto-posted entries are explicitly excluded from direct reversal by design) and two real financial statements — Income Statement (optional date range) and Balance Sheet (as-of-today, with a `balanced` boolean actually computed, not assumed). Sales and Purchase both continue auto-posting real, balanced entries via their own integration services; Purchase now posts input VAT to its own account rather than netting through Sales'. A `accounting:provision-defaults` console command backfills the new VAT account for tenants that registered before this sprint. No ZATCA e-invoicing (that work belongs to the earlier, separate single-file HTML application, not this Laravel codebase) |
| **Files involved** | ~22 (added `ProvisionAccountingDefaultsCommand`, extended `AccountingProvisioningService`/`AccountingService`/`ReportService`/`JournalEntry`/`JournalEntryResource`/`JournalEntryController`/`PurchaseAccountingIntegrationService`, new migration, 3 new test files) |
| **Database tables** | `chart_of_accounts`, `journal_entries` (now with `is_reversed`/`reversed_by_entry_id`, plus existing `source_type`/`source_id` traceability), `journal_entry_lines` (`2025_07_01_000400`, extended `2025_08_01_000500`, extended again `2025_11_01_000100`) |
| **API endpoints** | 6 (chart of accounts list/create/update, journal entries list/create/show, **+ reverse, new this sprint**) — auto-posted entries from both Sales and Purchase appear here too, and cannot be reversed through this endpoint by design |
| **Frontend pages** | Chart of Accounts (generic list), Journal Entries (custom debit/credit line builder, now with a Status column and Reverse action), **Income Statement and Balance Sheet (new this sprint)** |
| **Services** | `AccountingProvisioningService` (seeds the default COA at registration, now with a backfill-safe path for the new VAT account), `AccountingService::createEntry()` / `reverseEntry()` (new), `ReportService::incomeStatement()` / `balanceSheet()` (new) |
| **Tests** | `AccountingMvpTest` (original MVP-sprint coverage), `AccountingModuleIntegrationTest` (new this sprint, 6 cases: VAT account provisioning and backfill, Purchase's VAT split, reversal balance/rejection cases, financial statements against real activity), `AccountingExtensionTenantIsolationTest` (new, 2 cases), `AccountingStatementsReportsTest` (new, 1 case, in `tests/Feature/Reports/`) |
| **Missing items** | Accounting periods/period-close, multi-currency, budget vs. actual reporting, a KSA-specific COA template wired to ZATCA, record-level scoping, statement export/scheduling |

## HR & Payroll

| | |
|---|---|
| **Status** | Completed (audited depth, this sprint — built from zero, no prior data model existed) |
| **Completion** | Employees (full CRUD, optional link to a system User via `user_id`, real termination workflow, real Salary Structure assignment), Departments (reused the existing Platform Admin table), Designations, Shifts, Holidays (tenant-editable lookups), Attendance (real check-in/check-out, shift-aware lateness detection, manual HR marking), Leave Management (Leave Types, per-employee-per-year Leave Balances auto-provisioned on hire, Leave Requests with real balance validation and a real Attendance integration on approval), a real gross-to-net Payroll engine (`PayrollService::process()` — basic + allowances + approved overtime − deductions, one payslip per active employee per run, one balanced journal entry per run), Salary Structure/Allowances/Deductions via a single tenant-editable Salary Components engine, Overtime (documented 240-hour-month hourly-rate basis, configurable rate multiplier), Payslips (full line-item detail, generated→paid lifecycle), basic Recruitment (Job Openings → Candidates → Applications, with hiring creating a real Employee record — not just a status flag), basic Performance Reviews (cycle-based, real draft→submitted→acknowledged lifecycle, rating required before submission), and Employee Self-Service (every endpoint scoped server-side to the caller's own linked Employee record, never a client-supplied id). Deliberately did NOT invent Saudi GOSI contribution rates or income-tax brackets — real regulatory input this project has never been given; the Salary Components engine is real and generic enough for a tenant to model GOSI themselves once they have those rules |
| **Files involved** | ~95 (19 models, 16 repository interfaces + implementations, 11 services, 24 Form Requests, 16 API Resources, 18 controllers, 1 provisioning service + console command, 4 migrations, 3 test files) |
| **Database tables** | `designations`, `shifts`, `holidays`, `employees`, `leave_types`, `leave_balances`, `leave_requests`, `attendances`, `salary_components`, `employee_salary_components`, `overtime_records`, `payroll_runs`, `payslips`, `payslip_lines`, `job_openings`, `candidates`, `job_applications`, `performance_review_cycles`, `performance_reviews` (`2025_12_01_000100` through `000400`, RLS enabled and forced on all 19) |
| **API endpoints** | 60 (52 under `/hr`, 7 under `/ess`, 1 payroll summary report under `/reports`) |
| **Frontend pages** | HR Dashboard, Employees (with a termination action), Attendance, Leave Requests (approve/reject), Payroll Runs (process + mark-paid), Recruitment (openings + applications with an inline hire flow), Performance Reviews, My Self-Service (check-in/check-out, leave request, own attendance/leave/payslip history) |
| **Services** | `HrPayrollProvisioningService` (seeds default Leave Types/Salary Components/payroll accounts, backfill-safe), `EmployeeService`, `AttendanceService`, `LeaveService`, `OvertimeService`, `PayrollService`, `HrPayrollAccountingIntegrationService` (real journal-entry auto-posting, mirrors Sales/Purchase's pattern exactly), `RecruitmentService`, `PerformanceReviewService`, `HrDashboardService`, `EmployeeSelfService` |
| **Tests** | `HrPayrollModuleIntegrationTest` (8 cases: tenant provisioning, the backfill command, leave-balance provisioning on hire, shift-aware late check-in, leave balance rejection + approval deduction + attendance integration, the full payroll run → payslip → balanced accounting posting chain, the recruitment hire → real Employee integration, the performance review lifecycle), `HrPayrollExtensionTenantIsolationTest` (2 cases), `HrReportsDashboardAndEssTest` (2 cases: dashboard/report data shapes, ESS server-side scoping) |
| **Missing items** | GOSI/income-tax computation (needs real regulatory input, not guessed at), configurable standard working hours (240-hour-month is a documented constant, not yet a setting), cancelling an approved leave request (pending-only this sprint), payslip PDF export/email delivery, interview scheduling/offer letters/onboarding checklists, 360-degree performance feedback, record-level scoping, biometric/geolocation attendance |

## Reports & Analytics

| | |
|---|---|
| **Status** | Completed (audited depth, this sprint) |
| **Completion** | Fourteen prior cross-module reports (Sales/Purchase/Inventory summaries, Trial Balance, Sales by Customer/Product, Aging Receivables/Payables, Stock by Warehouse, Inventory by Category, Purchase by Supplier, Income Statement, Balance Sheet, Payroll Summary) plus, **new this sprint**: Executive Dashboard (real cross-module snapshot), KPI Dashboard (real month-over-month trends with a null — not fabricated-zero — change on no prior data), 4 CRM Reports (leads by source/status, opportunities by stage, a real conversion funnel — CRM never had its own reports before this sprint), Cash Flow (real cash-basis movement through the Cash account, named explicitly as single-account rather than a full indirect-method statement), VAT Report (output vs. input VAT off the existing split accounts), a Custom Report Builder (a saved, re-runnable definition with one allow-list — source key → real model + fixed column set — as its entire injection-safety model), real dependency-free file export (CSV, a real minimal XLSX via `ZipArchive`, a real minimal multi-page PDF — all rigorously verified with `qpdf`/`pdfinfo`/`unzip`, not just claimed), and Scheduled Reports (real recurring email delivery, deliberately scoped to saved Custom Reports since built-in statement-style reports have shapes a generic exporter can't represent honestly) |
| **Files involved** | ~30 (new: `AnalyticsDashboardService`, `CrmReportService`, `CustomReportService`, `ScheduledReportService`, `ReportExportService`, `ScheduledReportMail`, `AnalyticsController`, `CustomReportController`, `ScheduledReportController`, `ReportExportController`, extended `ReportController`/`ReportService`, 4 Form Requests, 2 API Resources, 2 models, 2 repository interfaces + implementations, 1 console command, 4 test files) |
| **Database tables** | `custom_reports`, `scheduled_reports` (`2026_01_01_000100`, RLS `2026_01_01_000200`) |
| **API endpoints** | 21 new (executive-summary, kpi-summary, cash-flow, vat-report, 4 CRM reports, generic export, full custom-reports CRUD + sources + run, full scheduled-reports CRUD + run-now) — **35 total report-related endpoints** |
| **Frontend pages** | Executive Dashboard, KPI Dashboard, CRM Reports (with real dependency-free SVG bar charts), Cash Flow, VAT Report, Custom Reports (build/run/export/delete), Scheduled Reports (build/run-now) — 7 new screens, plus export buttons added to the original Reports screen |
| **Services** | `AnalyticsDashboardService` (Executive + KPI dashboards), `CrmReportService`, `CustomReportService` (the allow-listed report-builder engine), `ScheduledReportService`, `ReportExportService` (real CSV/XLSX/PDF generation, zero Composer dependencies), extended `ReportService` (Cash Flow, VAT Report) |
| **Tests** | `ReportsAnalyticsIntegrationTest` (6 cases: dashboard shapes, real CRM report figures, the Custom Report Builder's allow-list rejecting bad input while correctly running valid definitions, Scheduled Report frequency computation + `Mail::fake()`-verified delivery, the built-in export endpoint, Cash Flow/VAT shapes), `ReportsAnalyticsTenantIsolationTest` (1 case), `ReportExportServiceTest` (4 unit cases verifying real CSV content, a structurally valid XLSX opened via `ZipArchive`, a structurally valid PDF with correct header/trailer, and real multi-page pagination) |
| **Missing items** | Cash Flow is single-account/cash-basis only, not a full indirect-method statement; statement-style reports (P&L, Balance Sheet, Cash Flow, VAT) aren't wired to the generic file-export endpoint (their nested shapes can't be flattened honestly — rebuild as a Custom Report instead); Scheduled Reports only cover saved Custom Reports; PDF export can't render Arabic (base Helvetica has no Arabic glyphs); XLSX has no styling/formulas/multiple sheets; `reports:process-scheduled` has never executed end-to-end in this sandbox (same standing `composer install`-blocked caveat as all real application code here); no drag-and-drop report designer; record-level scoping |

## AI Assistant

| | |
|---|---|
| **Status** | Completed (audited depth; extended to full requested scope across two sprints) |
| **Completion** | **First sprint** (unchanged): a real, provider-agnostic LLM integration layer (`LlmProviderInterface`) on top of a deterministic keyword-matched Q&A engine (8 grounded intents: Leads/Customers/Opportunities/Sales/Inventory/Purchase/HR & Payroll/Accounting-Cash), with `AnthropicLlmProvider` as the first real provider. **This sprint added**: a second real provider (`OpenAiLlmProvider`, same rigor — no SDK, no hardcoded key, request shape verified via `Http::fake()` despite `api.openai.com` not being reachable from this sandbox at all); real per-tenant provider selection (`ai_settings.provider_override` — a tenant picks *which platform-configured* provider they use, resolved safely at request time, with a tested fallback when the chosen provider has no real credentials, never a tenant-supplied key); `AiInsightService` powering AI Dashboard/Sales/Inventory/Financial/CRM Insights and AI Report Summaries (all reusing already-audited services' real data, all governed by the same LLM-or-deterministic degradation pattern chat established); real Automation Suggestions (idempotent — a persisting condition never opens a duplicate) tied to real AI Notifications via the existing `NotificationService`; a dedicated AI Activity Log audit trail (distinct from chat's own provider/model columns); real per-tenant AI Settings (master on/off, insights/notifications/suggestions on/off, provider override); and AI Prompt Management (6 tenant-editable keys with real defaults, never a blank prompt) |
| **Files involved** | ~38 total (first sprint: 12; this sprint: +26 — `OpenAiLlmProvider`, `AiInsightService`, `AiSettingsService`, `AiPromptService`, 4 new models, 4 repository interfaces + implementations, 5 controllers, 3 Form Requests, 4 API Resources, 3 test files) |
| **Database tables** | `ai_conversations`, `ai_messages` (`2025_07_01_000500`, extended `2026_02_01_000100`); **new this sprint:** `ai_settings`, `ai_prompt_templates`, `ai_activity_logs`, `ai_suggestions` (`2026_02_15_000100`, RLS `2026_02_15_000200`) |
| **API endpoints** | 4 from the first sprint + **15 new this sprint** (5 insight endpoints, report-summary, suggestions index/dismiss/mark-actioned, settings show/update, prompt-templates index/upsert/reset, activity-logs index) — **19 total** |
| **Frontend pages** | AI Assistant chat UI; **new this sprint:** AI Insights, AI Suggestions, AI Settings, AI Prompts, AI Activity Log — 5 new screens |
| **Services** | `AiAssistantService` (chat, 8 grounded intents), `AiInsightService` (5 insight types + report summaries + automation suggestions + notifications), `AiSettingsService`, `AiPromptService`, `AnthropicLlmProvider`, `OpenAiLlmProvider` |
| **Tests** | First sprint: `AiAssistantMvpTest`, `AnthropicLlmProviderTest`, `AiAssistantLlmFallbackTest`. **New this sprint:** `AiAssistantExtensionIntegrationTest` (10 cases — settings, disabled-insights honesty, real activity-log verification, suggestion idempotency with `Notification::fake()`, prompt resolve/override/reset, and the two safety-critical provider-override cases), `AiAssistantExtensionTenantIsolationTest` (2 cases), `OpenAiLlmProviderTest` (4 unit cases, mirroring the Anthropic provider's test rigor exactly) |
| **Missing items** | Only two real providers implemented (Anthropic, OpenAI) — the interface is ready for more; OpenAI has never been reachable at all in this sandbox (network policy); Automation Suggestions cover 3 conditions (overdue receivables, low stock, negative cash), not exhaustive; a dismissed suggestion doesn't auto-reopen if the condition recurs; Report Summaries take a generic JSON payload rather than being report-type-aware; no streaming responses, function-calling, or write actions (a deliberate safety boundary, not unfinished work); record-level scoping |

## Production Readiness / Infrastructure

| | |
|---|---|
| **Status** | Completed (real infrastructure, unexecuted outside this sandbox — see below) |
| **Completion** | Docker: a hardened multi-stage `Dockerfile` (non-root runtime user, opcache production tuning, a real `HEALTHCHECK`, fixing a prior `composer install ... \|\| true` that silently swallowed install failures), `.dockerignore` (a real gap — `.env`/`.git` were being sent into every build context before this), a production `docker-compose.prod.yml` override (no source volume mounts — code comes from the built image; Postgres/Redis stop publishing ports to the host; `mailhog` gated behind a real Compose `profiles` mechanism, replacing a first-draft `deploy.replicas: 0` that silently does nothing outside Swarm mode — caught and fixed before shipping). CI/CD: a real GitHub Actions workflow (`composer install`, a full `php -l` lint sweep, real migrations against a real Postgres service container, the real test suite via `php artisan test --parallel`, a `composer audit` dependency check, and a deploy job) — the one piece of this project's automation that can actually execute, since GitHub's runners have real internet access unlike this development sandbox. Deployment: `scripts/deploy.sh` (pull → install → migrate → rebuild every cache → restart PHP-FPM → `queue:restart`, stopping at the first failure), `scripts/rollback.sh` (deliberately does not auto-rollback migrations — some in this project are additive by design), `scripts/install.sh`. Security hardening (OWASP): a `SecurityHeaders` middleware (CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy, conditional HSTS) applied globally, a real `auth` rate limiter (10/min, IP+identifier keyed) closing a genuine gap — login/OTP/password-reset previously shared only the generic 60/min API-wide throttle — plus matching `limit_req` zones in nginx as defense-in-depth. Monitoring: a real deep `GET /api/v1/health` (database/cache/queue connectivity, distinct from Laravel's own `/up` which only confirms the app booted). Error tracking: a real, dependency-free webhook-based `ErrorTrackingService` wired into the exception pipeline (no SDK — `composer install` still blocked). Automated backups: a real `pg_dump`-based `backup:database` command with retention pruning, scheduled daily. Database optimization: 5 new composite indexes on columns Reports/Dashboards/AI Insights actually query (3 originally-planned indexes were found to already exist from earlier sprints and were not duplicated). **Two real, previously-unnoticed bugs found and fixed**: `NotificationMail`/`ScheduledReportMail` never actually implemented `ShouldQueue` despite `QUEUE_CONNECTION=redis` being configured since Foundation — every notification/scheduled-report email has been sending synchronously; and the `failed_jobs` table, required for real queue reliability, never existed. Two more real gaps found and fixed in the migration-verification shim itself (`useCurrent()`, `longText()`). Four new production docs written in full |
| **Files involved** | ~30 (`docker/php/Dockerfile`, `docker/nginx/default.conf`, `docker-compose.yml`, `docker-compose.prod.yml`, `.dockerignore`, `.github/workflows/ci.yml`, `scripts/deploy.sh`, `scripts/rollback.sh`, `scripts/install.sh`, `SecurityHeaders` middleware, `HealthCheckController`, `ErrorTrackingService`, `BackupDatabaseCommand`, `config/services.php`, 2 migrations, `routes/console.php` scheduler entries, 4 doc files, 3 test files) |
| **Database tables** | No new business tables — `failed_jobs` (`2026_03_01_000100`, a real gap closed) and composite indexes on `sales_invoices`/`supplier_bills`/`journal_entries`/`attendances`/`leave_requests` (`2026_03_01_000200`) |
| **API endpoints** | 1 new (`GET /api/v1/health`) — 354 total |
| **Services** | `ErrorTrackingService` (real webhook delivery, never throws), `SecurityHeaders` middleware, `HealthCheckController` |
| **Tests** | `HealthCheckTest` (2 cases: public accessibility, real component status), `SecurityHardeningTest` (2 cases: real headers on every response, the real `auth` rate limiter actually blocking after its stated limit), `ErrorTrackingServiceTest` (3 unit cases via `Http::fake()`: no-op when unconfigured, real structured payload delivery, never throwing on a failed webhook) |
| **Missing items** | Nothing in this sprint's scope has actually executed outside this development sandbox — no real GitHub Actions run, no real server deployment, no real backup taken from a live database, no real Docker image built (`composer install` reaching Packagist has been blocked in this sandbox across every sprint; GitHub Actions runners can do this for real, but no workflow run has happened yet since there's no git remote here). Only two real LLM providers exist for a webhook-based error tracker's own alternative — a true Sentry/Bugsnag SDK integration would need `composer install` to work here first. No blue-green/zero-downtime deploy automation beyond a simple restart. No WAL-based point-in-time recovery (documented as a database-server-level concern, not application code, in `docs/BACKUP_RESTORE_GUIDE.md`) |

## Travel ERP

| **Status** | Not Started | — this is the first appearance of "Travel ERP" anywhere in this project's history (checked against every prior build request). No design discussion, no schema, no code. Flagging this explicitly since the audit brief listed it alongside modules that do have history (Sales, HR, etc.) — worth confirming with stakeholders whether this belongs on the roadmap at all before scoping it. |

## Billing (platform subscription billing)

| **Status** | Not Started | — `tenants.status` (`trial`/`active`/`past_due`/`suspended`/`cancelled`) is a lifecycle flag with no engine behind it: no `subscription_plans` table, no payment gateway integration, no platform-invoice generation, nothing that actually transitions a tenant between these statuses automatically. A tenant registers into `trial` and nothing currently ever moves it anywhere else. |

## Notifications

| | |
|---|---|
| **Status** | Completed (Sprint 1 — Platform Administration) |
| **Completion** | 100% of in-app + email; SMS/WhatsApp/push transport stubbed |
| Covered under Platform Administration above — not duplicated here. |

## API

| | |
|---|---|
| **Status** | Completed for what exists |
| **Endpoints** | 354 total (353 as of the last audit + 1 new this sprint: `GET /api/v1/health`), all under `/api/v1`, precisely counted from the actual route file (not estimated) |
| **Consistency** | Every non-resource-collection response wraps in `{"data": ...}` (fixed during CRM Sprint 1 — see `CRM_MODULE.md`); every error response follows the same `{"error","message","details"}` shape (`bootstrap/app.php`'s exception handling) |
| **Versioning** | `/api/v1` prefix only — no `v2`, no deprecation policy document yet (not needed until a breaking change is actually made) |
| **Missing items** | No OpenAPI/Swagger spec generated from the actual Laravel routes — the `openapi.yaml` that exists in prior project outputs describes the superseded pre-Laravel architecture and does **not** reflect these 168 real endpoints (see `AUDIT_REPORT.md` Part 4) |

## Authentication

Covered under Foundation above.

## RBAC

| | |
|---|---|
| **Status** | Completed, and correctly extensible |
| **Modules configured** | `admin`, `dashboard`, `core`, `crm` (in `config/permissions.php`) |
| **Default roles** | `super_admin`, `company_owner`, `admin`, `manager`, `sales`, `accountant`, `hr`, `inventory`, `cashier`, `employee` — provisioned automatically at tenant registration (`RoleProvisioningService`), fully editable per tenant afterward |
| **Record-level scoping** | Implemented for the first time in CRM Sprint 1 (`LeadPolicy`) — the pattern the Foundation's docs described but didn't yet need |

## Multi-Tenancy

| | |
|---|---|
| **Status** | Completed, reviewed, and hardened |
| **Enforcement** | PostgreSQL RLS (`FORCE ROW LEVEL SECURITY`) on all 32 tenant-owned tables + application-layer `BelongsToTenant` scope + `BindAuthenticatedTenant` middleware verifying token-tenant match |
| **Review history** | A dedicated audit (`TENANT_ISOLATION_REVIEW.md`) found and fixed 5 real issues, 2 of them critical (registration would have failed outright under real RLS). **As of the Database Verification sprint, this is no longer a claim resting on code review alone** — a real PostgreSQL instance confirmed a cross-tenant read returns 0 rows (1 row once `is_super_admin` is set) and a cross-tenant write is rejected with `ERROR: new row violates row-level security policy`, both against the actual RLS policies in the actual migrations. See `DATABASE_VERIFICATION.md`. |

## Super Admin

| | |
|---|---|
| **Status** | Completed (Console basics) |
| **Completion** | 100% of the scoped "console basics" (list tenants, view metrics, suspend/reactivate); no billing-derived metrics, no cross-tenant search, no audit-log viewer within the console itself |
| **Files involved** | ~10 (2 controllers, 2 services, 1 middleware, 1 resource, 1 request, 1 migration, 1 Blade view, 1 web route file) |
| **Database tables** | No new tables — extends `tenants` with `suspension_reason` + `suspended_by_user_id` (migration `2025_04_01_000100`) |
| **API endpoints** | `GET /admin/platform/metrics`, `GET /admin/platform/tenants`, `GET /admin/platform/tenants/{tenant}`, `POST /admin/platform/tenants/{tenant}/suspend`, `POST /admin/platform/tenants/{tenant}/reactivate` — 5 endpoints |
| **Frontend pages** | One: `resources/views/super-admin/console.blade.php` — a real, functional static page (login, live metrics, tenant table with working suspend/reactivate), vanilla JS calling the same JSON API. The first frontend page in this repository — see `SUPER_ADMIN_CONSOLE.md` for why it was scoped this narrowly rather than as a project-wide frontend decision |
| **Services** | `SuperAdminTenantService` (suspend/reactivate as real state transitions — revokes every session, dual-sided audit logging), `PlatformMetricsService` (real cross-tenant counts, no fabricated revenue figures) |
| **Repositories** | Reuses the existing `TenantRepository` from Foundation — no new repository needed |
| **Controllers** | `PlatformTenantController`, `PlatformMetricsController` (`Api/V1/SuperAdmin/*`) |
| **Middleware** | `EnsureSuperAdmin` (new) — gates the console route group by identity, not by a tenant-RBAC permission |
| **Tests** | `SuperAdminConsoleTest` (10 cases: authorization, cross-tenant listing, real metrics, suspend/reactivate lifecycle, session revocation, tenant isolation of the suspend action, tenant-side activity log visibility, page rendering), `PlatformMetricsServiceTest` (unit) |
| **Missing items** | No cross-tenant search/filter beyond what `TenantRepository`'s existing `status`/`name`/`subdomain` filters provide; no in-console audit-log viewer; no automated status transitions (still entirely manual) |
| **Technical debt** | None found — this sprint's one bug (`->each()` called on a query builder, not a Collection) was caught before shipping, not left in |
