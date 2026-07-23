# SoudaCore ERP — User Guide

A day-to-day guide for someone *using* SoudaCore — a salesperson,
accountant, HR staff member, warehouse clerk — not administering it.
For admin/operational tasks (managing users, roles, backups,
deployments), see `docs/ADMIN_GUIDE.md` instead; this guide doesn't
repeat that content.

## Logging in

Go to your company's SoudaCore console URL (given to you by your
admin). Enter your email and password. If your admin enabled OTP for
your account, you'll be asked for a one-time code sent to you
(logged/emailed/texted depending on your deployment's configuration)
after your password is accepted.

## Finding your way around

The left-hand navigation is grouped by module — you'll only see the
groups your role actually has access to (ask your admin if something
you expect to see is missing; see `docs/ADMIN_GUIDE.md`'s "Managing
users and roles" section, which they can use to check or grant it).

## Common workflows

### Sales: from lead to paid invoice
1. **CRM → Leads** — add a new lead, or one arrives from another
   source. Assign it to a salesperson.
2. **CRM → Leads → Convert** — once qualified, convert the lead to a
   real Customer.
3. **Sales → Orders** — create a Sales Order for the customer.
4. **Sales → Delivery Notes** — record what actually shipped.
5. **Sales → Invoices** — issue the invoice (this posts a real
   accounting entry automatically — Accounts Receivable, Revenue, VAT
   Payable — you don't need to touch Accounting directly for this).
6. **Sales → Payments** — record the customer's payment against the
   invoice when it arrives.

### Purchasing: from order to paid bill
1. **Purchase → Purchase Orders** — create and send to your supplier.
2. **Inventory → Goods Receiving** — record what actually arrived.
3. **Purchase → Supplier Bills** — the supplier's invoice; approving
   it posts a real accounting entry (Inventory, Accounts Payable, VAT
   Recoverable).
4. **Purchase → Supplier Payments** — record your payment.

### Checking your leave balance and requesting time off
1. **HR & Payroll → My Self-Service** — see your own attendance,
   leave balance, and payslips.
2. Submit a leave request from the same screen; your manager approves
   or rejects it from **HR & Payroll → Leave Requests** (if they have
   that permission).

### Checking stock levels
**Inventory → Products** shows real current stock per warehouse.
Products at or below their reorder point are flagged — see
**AI Assistant → AI Insights → Inventory** for a real, narrated
summary of what needs reordering right now, or
**AI Assistant → AI Suggestions** for a standing list the system
raised automatically.

### Using the AI Assistant
**AI Assistant** in the left nav opens a real chat — ask it things
like *"how many open leads do we have?"* or *"what's our cash
position?"* and it answers from your company's real, current data
(never fabricated numbers — see `docs/AI_ASSISTANT_SPRINT.md`). The
top of the screen shows whether it's running on a real AI provider or
the built-in deterministic answers, depending on what your admin has
configured (`docs/ADMIN_GUIDE.md`'s "AI Assistant administration"
section).

**AI Insights** gives a one-click narrated summary for Dashboard,
Sales, Inventory, Financial, or CRM data — useful for a quick read
without digging through a full report.

### Running and exporting reports
**Reports** in the left nav has the standard built-in reports (Sales,
Purchase, Inventory, Trial Balance, Income Statement, Balance Sheet,
and more). Most support exporting to CSV, PDF, or Excel — look for the
export buttons next to each report.

If you need a report the built-in list doesn't cover,
**Reports → Custom Reports** lets you build one: pick a data source,
the columns you want, and any filters — no technical knowledge
required beyond knowing what data you're looking for. You can also
schedule a Custom Report to email itself to you automatically on a
real recurring basis (**Reports → Scheduled Reports**).

## Getting help

For anything this guide doesn't cover, or if something isn't working
the way this guide describes, contact your company's SoudaCore
administrator first (`docs/ADMIN_GUIDE.md` is written for them) — they
can check your permissions, your AI/notification settings, or escalate
a real bug if one exists.
