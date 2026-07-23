# HR & Payroll Module — Production-Ready Completion Sprint

Built the HR & Payroll module from zero — no Employee data model existed
before this sprint, only the `hr` role code as an RBAC label (see
PROJECT_STATUS.md's standing note) — up to the same audited depth CRM,
Sales, Inventory, Purchase, and Accounting already carry: real
integration tests, empirically verified tenant isolation, and genuine
cross-module integration with Accounting, User Management, and
Notifications, not a self-contained island.

---

## Scope delivered

**Employees** — full CRUD, linked optionally to a system User account
(`employees.user_id`, nullable — not every employee needs a login),
real termination workflow (rejects double-termination), and a real
Salary Structure assignment (`assignSalaryComponents()`) that fully
replaces an employee's assigned components rather than patching them
incrementally.

**Departments** — deliberately reused the existing Platform
Administration `departments` table rather than building a duplicate
one; Employees, Designations, and Job Openings all link to it.

**Designations, Shifts, Holidays** — tenant-editable lookup tables,
the same pattern as LeadSource/LeaveType elsewhere in this project.

**Attendance** — real check-in/check-out with shift-aware lateness
detection (more than 10 minutes past a shift's `start_time` marks the
day 'late', not just 'present'), real hours-worked computation on
check-out, and a manual-mark path for HR to record past/explicit
attendance (absence, half-day).

**Leave Management** — Leave Types (tenant-editable, paid/unpaid),
real per-employee-per-year Leave Balances (auto-provisioned on hire
for every active leave type), and a Leave Request workflow with real
balance validation: a paid-leave request that would exceed the
employee's remaining balance is rejected outright at request time, not
left for the approver to catch by hand. Approving a request deducts
the real balance and marks every calendar day in the range 'on_leave'
in Attendance — leave isn't invisible to attendance tracking.

**Payroll** — the core deliverable. `PayrollService::process()`
computes, for every active employee in one transaction: basic salary +
assigned allowances + approved overtime for the period − assigned
deductions = net pay, generates a real Payslip with full line-item
detail per employee, rolls the run's totals up, and posts **one** real
balanced journal entry for the whole run (mirroring how a Sales
Invoice posts once regardless of line-item count): Dr `5200 Salaries &
Wages Expense` (gross), Cr `1000 Cash` (net), Cr `2200 Salaries
Payable` (deductions withheld, when present) — new accounts this
sprint adds via the same guarded-backfill pattern the Accounting
sprint used for `2110 VAT Recoverable`, complete with a
`hr:provision-defaults` console command for existing tenants.

**Salary Structure, Allowances, Deductions** — a single, real,
tenant-editable Salary Components engine (`salary_components` +
`employee_salary_components`) rather than three separate systems:
each component is typed `allowance` or `deduction`, and an employee's
actual structure is the set of components assigned to them. **Two
defaults are seeded per tenant** (Housing Allowance, Transport
Allowance) — enough to prove the engine end-to-end without inventing a
Saudi-specific benefits policy this project was never given as real
input.

**Overtime** — real hourly-rate computation
(`basic_salary / 240` — an explicitly documented standard-240-hour-month
assumption, not a silent guess) × hours × a configurable rate
multiplier (default 1.5×), with an approve/reject workflow. Only
approved overtime for the payroll period feeds into a payslip.

**Payslips** — real structured records with line-item detail (basic,
each allowance, overtime, each deduction), a `generated -> paid`
lifecycle tied to the payroll run being marked paid.

**Payroll Reports** — a new `payrollSummary()` report (real run
totals plus a real department breakdown), joining `ReportService`
alongside the existing Sales/Purchase/Inventory/Accounting reports.

**Employee Self-Service** — a dedicated `ess.*` permission module,
deliberately separate from `hr_payroll.*`: every ESS endpoint resolves
the calling user's own Employee record server-side
(`employees.user_id = auth user id`) and **never** accepts a
client-supplied employee id. A user with no linked Employee record
gets a clear, explicit error, not an empty "success" that could be
mistaken for having no data. Own profile, own attendance (+ self
check-in/check-out), own leave requests (view + submit against the
same real `LeaveService` validation HR uses), own payslips.

**Recruitment (basic)** — Job Openings → Candidates → Job Applications,
with a real status pipeline (`applied -> screening -> interview ->
offered -> hired/rejected`). **Hiring is a real integration, not a
status flag**: marking an application 'hired'
(`RecruitmentService::hire()`) creates the actual Employee record from
the candidate's details — the same reasoning Purchase Returns
auto-generating a linked Debit Note followed.

**Performance Reviews (basic)** — cycle-based (e.g. "Q3 2026"), one
review per employee per cycle (a real unique constraint, not just
convention), with a genuine `draft -> submitted -> acknowledged`
lifecycle — submission is rejected without a rating first, not
silently accepted with a null one.

## Integration with Accounting, User Management, and Notifications

- **Accounting**: `HrPayrollAccountingIntegrationService` posts a real
  balanced journal entry per processed payroll run, mirroring
  `SalesAccountingIntegrationService`/`PurchaseAccountingIntegrationService`'s
  exact pattern including loud failure on a missing standard account.
- **User Management**: `employees.user_id` links to the existing
  `users` table; Employee Self-Service resolves entirely through this
  link rather than duplicating identity.
- **Notifications**: approving or rejecting a leave request notifies
  the employee via the existing `NotificationService` — real in-app
  delivery (plus whatever channels the recipient has enabled), the
  same mechanism every other module uses, not a bespoke one.

## Database — verified for real, standing practice held

Four new migrations (19 new tables: HR core — designations, shifts,
holidays, employees, leave types/balances/requests, attendances;
Payroll — salary components, employee salary structure, overtime,
payroll runs, payslips, payslip lines; Recruitment/Performance — job
openings, candidates, applications, review cycles, reviews) plus RLS
enabled and forced on all 19. All 77 migrations (73 prior + 4 new) run
cleanly against real PostgreSQL. **Caught and fixed a real gap in the
verification tool itself**, the same kind of fix CRM Sprint 3 made:
the schema shim was missing `time()`/`dateTime()` column-type support,
needed for `shifts.start_time`/`end_time` and
`attendances.check_in`/`check_out` — fixed in `schema_shim.php`
directly rather than worked around with a different column type.

## RBAC

Two new permission modules: `hr_payroll` (view/create/edit/delete/
approve/export — Owner/Admin/HR manage it fully, Manager can view and
approve but not touch payroll runs directly) and `ess` (view/create —
granted broadly to every default role, since anyone with a login may
also have a linked Employee record).

## Repository Pattern, Service Layer, Validation, Audit Logs

16 repository interfaces + Eloquent implementations (child/line-item
tables — leave balances, employee salary components, payslip lines —
deliberately don't get their own repositories, consistent with how
supplier bill items or payslip lines elsewhere in this project don't
either). 11 services carrying all real business logic. 24 Form Request
classes, every one validating tenant-scoped foreign keys via
`Rule::exists(...)->where('tenant_id', ...)`. `Auditable` attached to
every aggregate-root model that isn't a high-volume child record.

## Frontend

A new "HR & Payroll" nav group (HR Dashboard, Employees, Attendance,
Leave Requests, Payroll Runs, Recruitment, Performance Reviews, My
Self-Service) added to `console.blade.php` — real screens backed by
real endpoints, not mockups: employee CRUD with a termination action,
a payroll-run processing modal, leave approve/reject actions, a
recruitment pipeline with an inline hire flow, and a full My
Self-Service page (check-in/check-out, leave request, own attendance/
leave/payslip history). Verified the same way every prior sprint's
frontend work has been: the embedded JavaScript (now 1,750 lines)
extracted and run through `node --check`, and grepped for Blade `{{`
collisions (none).

## Tests

`HrPayrollModuleIntegrationTest` (8 cases): tenant provisioning of
leave types/salary components/payroll accounts, the backfill command,
leave-balance provisioning on hire, shift-aware late check-in, leave
balance rejection + real approval deduction + attendance integration,
the full payroll run → payslip → balanced accounting posting chain
(with a duplicate-period rejection), the recruitment hire → real
Employee integration (with a double-hire rejection), and the
performance review rating-required → submit → acknowledge lifecycle.
`HrPayrollExtensionTenantIsolationTest` (2 cases): raw-query
invisibility, independent per-tenant employee numbering.
`HrReportsDashboardAndEssTest` (2 cases): dashboard/report data-shape
smoke test, and Employee Self-Service's server-side scoping (a user
with no linked Employee gets a clear error; a linked user only ever
sees their own data).

## What's still explicitly out of scope

**GOSI/income-tax computation**: deliberately NOT built — this
project has never been given the real Saudi GOSI contribution rates
or income-tax brackets as business input, and guessing at them would
be worse than not having the feature (see PROJECT_STATUS.md's standing
note, unchanged since before this sprint). A tenant models GOSI today
as a real percentage-of-basic deduction Salary Component once they
have those rules — the engine is real and generic, not blocked on this,
but a dedicated GOSI calculator is not what shipped.
**Configurable standard working hours**: the overtime hourly-rate
divisor (240 hours/month) is a documented constant, not yet a
per-tenant setting. **Cancelling an approved leave request** (balance/
attendance reversal): only pending requests can be cancelled this
sprint. **Payslip PDF generation/export, email delivery of payslips**:
data-only this sprint. **Interview scheduling, offer letters,
onboarding checklists** (Recruitment): the basic pipeline only.
**360-degree/multi-rater performance feedback**: one reviewer per
review this sprint. **Record-level scoping**: still module-level RBAC,
consistent with every other module's current bar. **Biometric/
geolocation attendance**: manual check-in/check-out and HR-marked
entries only.
