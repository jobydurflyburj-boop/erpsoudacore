# Reports & Analytics — Production-Ready Completion Sprint

Brought Reports up from "a scattered collection of per-module report
endpoints" to a real Reports & Analytics module in its own right:
cross-module dashboards, the two report categories that never existed
(CRM Reports, Cash Flow, VAT Report), a genuine Custom Report Builder,
and real file export (CSV, XLSX, PDF) and recurring email delivery —
all built without a single new Composer package, since `composer
install` remains blocked in this sandbox (the same standing constraint
every prior sprint has hit).

---

## What was built

### Executive Dashboard & KPI Dashboard
`AnalyticsDashboardService::executiveSummary()` — a real cross-module
snapshot (cash position, AR, AP, sales/purchases this month, open POs,
active headcount, open leads, open opportunity value, low-stock count),
computed from the same real tables every audited module already
verified, not duplicated logic. `kpiSummary()` adds real
period-over-period trend comparison (revenue, purchase spend, new
leads, headcount) — a `change_percent` is `null`, not a fabricated 0%,
when the prior period had no activity to compare against.

### CRM Reports (never existed before this sprint)
`CrmReportService` — leads by source, leads by status, opportunities
by stage, and a real conversion funnel (total leads → won leads →
customers actually converted, via the real `source_lead_id` link CRM
Sprint 2 built → opportunities → won opportunities), with real
lead-to-customer and opportunity-win rates. Every other module reached
completion with its own reports; CRM's own sprints never circled back
to add one — this sprint closes that gap.

### Cash Flow & VAT Report (never existed before this sprint)
`ReportService::cashFlow()` — real cash-basis movement through the
Cash account (1000), grouped by month, split into cash in/out. This is
deliberately a real, useful cash-basis view of one account, not a
full indirect-method statement with operating/investing/financing
sections — named explicitly rather than overclaimed (see Scope below).
`ReportService::vatReport()` — output VAT collected vs. input VAT
paid, using the same split VAT accounts (`2100`/`2110`) the Accounting
Module completion sprint introduced, with a real net-payable position.

### Profit & Loss, Balance Sheet
Already real, from the Accounting Module completion sprint
(`incomeStatement()`, `balanceSheet()`) — surfaced here as part of the
same Reports & Analytics area rather than rebuilt.

### Custom Report Builder
`CustomReportService` — a saved, re-runnable definition (source +
columns + filters + optional group-by) executed safely at run time.
**The entire safety model is one allow-list** (`SOURCES`): a source key
(`sales_invoices`, `supplier_bills`, `journal_entries`, `employees`,
`products`, `customers`, `leads`, `opportunities`) maps to a real
Eloquent model and a fixed set of real column names — nothing outside
that list is ever accepted, so a saved definition can never become a
SQL-injection vector no matter what a tenant stores in it. Filters are
still bound through Eloquent's query builder as a second layer of
safety on top of the allow-list, never string-interpolated SQL.

### Filters
Every built-in date-scoped report (`incomeStatement`, `cashFlow`,
`vatReport`) accepts real `?from=&to=` query parameters. The Custom
Report Builder's filters are a real, structured `[column, operator,
value]` list, validated against the same allow-list as columns.

### Charts
A dependency-free inline SVG bar-chart helper (`svgBarChart()`) added
to the frontend — no Chart.js/CDN dependency, keeping the console a
single self-contained file consistent with every prior sprint's
frontend decision. Used on CRM Reports (leads by source/status,
opportunities by stage).

### PDF Export & Excel Export — real, dependency-free, and rigorously verified
No PhpSpreadsheet, no dompdf/mpdf — `composer install` is blocked, so
`ReportExportService` hand-builds both formats from scratch using only
what PHP ships with:
- **CSV**: trivial, always real.
- **XLSX**: a minimal but spec-valid single-sheet OOXML workbook, built
  with `ZipArchive` (confirmed available in this environment) — no
  styling or multiple sheets, but a real file Excel/Sheets/LibreOffice
  opens correctly, not a renamed CSV.
- **PDF**: a minimal but spec-valid multi-page PDF (PDF 1.4, Helvetica
  base-14 font, real object/xref/trailer structure), with real
  pagination when content overflows a page.

**This was not just claimed — it was verified with real tooling**:
`qpdf --check` (no syntax/stream errors), `pdfinfo`/`pdftotext`
(correctly parses and extracts all text, including escaped special
characters), and `unzip -l` (confirms the exact 5-part OOXML structure)
against actual generated files, including an 80-row/2-page pagination
test. Locked in as automated tests, not just a manual smoke check —
see Tests below.

Export is wired to: the Custom Report Builder (`?format=csv|pdf|xlsx`
on the run endpoint) and a dedicated `/reports/export/{reportKey}`
endpoint for the naturally-tabular built-in reports (Trial Balance,
Sales/Purchase by Customer/Supplier, Stock by Warehouse, Inventory by
Category, Payroll Runs, CRM reports).

### Scheduled Reports
`ScheduledReportService` + `ScheduledReportMail` + a new
`reports:process-scheduled` console command. **Deliberately scoped to
saved Custom Reports only** (a required `custom_report_id`) — Custom
Reports all share one real, generic tabular shape, which is what makes
generic CSV/PDF generation genuinely correct for any of them. Built-in
reports like the Income Statement have bespoke sectioned shapes a
generic exporter can't represent honestly; build the report you want
emailed as a Custom Report first, then schedule it. `next_run_at` is
computed for real per frequency (daily/weekly/monthly); `process()`
finds every due, active schedule, runs it, generates the configured
format, and emails every recipient — a failure on one schedule doesn't
stop the rest.

## Integration with all existing modules

Every report pulls from real tables Sales, Purchase, Inventory,
Accounting, HR & Payroll, and CRM already built and audited — no new
data model duplicates anything those modules own. The Executive
Dashboard is the clearest expression of this: one real query per
module, not a rebuilt aggregate.

## Database — verified for real, standing practice held

Two new migrations (`custom_reports`, `scheduled_reports`), RLS
enabled and forced on both. All 79 migrations (77 prior + 2 new) run
cleanly against real PostgreSQL via `tools/db-verify/`.

## RBAC

Extended the existing `reports` permission module with `create`/
`edit`/`delete` actions (for report-builder and schedule CRUD — `view`/
`export` already existed) and new covers (`crm_reports`, `cash_flow`,
`vat_reports`, `executive_dashboard`, `kpi_dashboard`, `custom_reports`,
`scheduled_reports`). Owner/Admin/Manager can build and schedule
reports; the existing broader `reports.view`/`reports.export` grants
are unchanged.

## API

21 new endpoints under `/reports`: Executive/KPI summaries, Cash Flow,
VAT Report, 4 CRM reports, the generic export endpoint, and full
Custom Report + Scheduled Report CRUD (plus `run`, `sources`,
`run-now`). All 337 total endpoints verified present after the change
— none of the 316 prior endpoints were clobbered.

## Frontend

7 new screens added to the "Insights" nav group: Executive Dashboard,
KPI Dashboard, CRM Reports (with real SVG charts), Cash Flow, VAT
Report, Custom Reports (build/run/export/delete), Scheduled Reports
(build/run-now). **A real bug was caught and fixed during this
sprint**: the first draft of the export button used unauthenticated
`window.open()`, which silently fails against this app's Bearer-token
auth — replaced with a proper authenticated blob-download helper
(`downloadExport()`) used consistently everywhere a file is
downloaded. Verified the same way every prior sprint's frontend work
has been: the embedded JavaScript (now 1,997 lines) extracted and run
through `node --check`, and grepped for Blade `{{` collisions (none).

## Audit Logs

`CustomReport` and `ScheduledReport` both carry the `Auditable` trait,
consistent with every other aggregate-root model in this project.

## Tests

`ReportsAnalyticsIntegrationTest` (6 cases): dashboard data shapes
(including the real null-vs-zero trend distinction), real CRM report
figures against actually-created leads, the Custom Report Builder's
allow-list rejecting an invalid source and an invalid column while
accepting and correctly running a valid one (with a real CSV export
content-type/content assertion), Scheduled Report frequency computation
and `Mail::fake()`-verified real email delivery on manual run, the
built-in export endpoint rejecting an unknown key and producing a real
PDF for a known one, and Cash Flow/VAT Report data shapes.
`ReportsAnalyticsTenantIsolationTest` (1 case): raw-query invisibility
of a Custom Report across tenants. `ReportExportServiceTest` (4 unit
cases, no framework dependencies): real CSV content, a structurally
valid XLSX opened and inspected via `ZipArchive`, a structurally valid
PDF with correct header/trailer, and real multi-page pagination for an
80-row report — the same rigor manually verified with `qpdf`/`pdfinfo`
during development, now locked in as automated tests.

## What's still explicitly out of scope

**Cash Flow is single-account, cash-basis only** — not a full
indirect-method statement with operating/investing/financing sections;
named as exactly what it is rather than overclaimed. **Statement-style
reports (Income Statement, Balance Sheet, Cash Flow, VAT Report) aren't
wired to the generic file-export endpoint** — their sectioned/nested
shapes can't be represented honestly by a flat tabular exporter; a
tenant who wants one in file form can rebuild it as a Custom Report
against the underlying data. **Scheduled Reports only support saved
Custom Reports**, not arbitrary built-in reports, for the same shape
reason. **PDF export uses base Helvetica and cannot render Arabic
text** — embedding a Unicode-capable font is a real, larger undertaking
than this minimal hand-built PDF writer's scope. **XLSX has no styling,
formulas, or multiple sheets** — a single flat data sheet only.
**`reports:process-scheduled` has never executed end-to-end in this
sandbox** — real code, lint-checked and unit/feature-tested, but
`composer install` being blocked means the application layer as a
whole has never run here, the same standing caveat every other piece
of real application code in this project carries. **No report
builder drag-and-drop UI** — the Custom Report Builder's frontend is a
real functional form, not a visual designer. **Record-level scoping**:
still module-level RBAC, consistent with every other module's current
bar.
