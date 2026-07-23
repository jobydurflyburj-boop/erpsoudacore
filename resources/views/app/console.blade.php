<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SoudaCore ERP</title>
<style>
:root{--primary:#4f46e5;--primary-dark:#3730a3;--bg:#f4f5f9;--card:#fff;--text:#1e1e2e;--muted:#6b7280;--border:#e5e7eb;--green:#059669;--red:#dc2626;--amber:#d97706;}
*{box-sizing:border-box;}
body{margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:var(--bg);color:var(--text);}
#login-screen{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#4f46e5,#7c3aed);}
.login-card{background:#fff;border-radius:16px;padding:40px;width:380px;box-shadow:0 20px 60px rgba(0,0,0,.25);}
.login-card h1{margin:0 0 4px;font-size:22px;}
.login-card p{color:var(--muted);margin:0 0 24px;font-size:14px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:13px;font-weight:600;margin-bottom:5px;color:#374151;}
.field input,.field select,.field textarea{width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:14px;}
.btn{cursor:pointer;border:none;border-radius:8px;padding:10px 16px;font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;}
.btn-primary{background:var(--primary);color:#fff;width:100%;}
.btn-primary:hover{background:var(--primary-dark);}
.btn-sm{padding:6px 10px;font-size:12px;border-radius:6px;}
.btn-outline{background:#fff;border:1px solid var(--border);color:var(--text);}
.btn-danger{background:#fee2e2;color:var(--red);}
.btn-success{background:#d1fae5;color:var(--green);}
.error-text{color:var(--red);font-size:13px;margin-top:8px;}
#app{display:none;min-height:100vh;}
.layout{display:flex;min-height:100vh;}
.sidebar{width:230px;background:#1e1b3a;color:#c7c5d6;flex-shrink:0;padding:18px 0;}
.sidebar .brand{padding:0 20px 18px;font-weight:800;font-size:17px;color:#fff;border-bottom:1px solid #33305a;margin-bottom:10px;}
.nav-item{display:block;padding:10px 20px;color:#c7c5d6;text-decoration:none;font-size:14px;cursor:pointer;border-left:3px solid transparent;}
.nav-item:hover{background:#2a2650;color:#fff;}
.nav-item.active{background:#2a2650;color:#fff;border-left-color:#818cf8;}
.nav-group{padding:14px 20px 4px;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#6f6a95;}
.main{flex:1;display:flex;flex-direction:column;min-width:0;}
.topbar{background:#fff;border-bottom:1px solid var(--border);padding:14px 24px;display:flex;justify-content:space-between;align-items:center;}
.topbar h2{margin:0;font-size:18px;}
.content{padding:24px;overflow-y:auto;flex:1;}
.card{background:var(--card);border-radius:10px;padding:20px;margin-bottom:18px;box-shadow:0 1px 3px rgba(0,0,0,.06);}
.grid{display:grid;gap:16px;}
.grid-4{grid-template-columns:repeat(auto-fit,minmax(180px,1fr));}
.grid-2{grid-template-columns:repeat(auto-fit,minmax(320px,1fr));}
.metric-value{font-size:26px;font-weight:800;}
.metric-label{font-size:12px;color:var(--muted);margin-top:2px;}
table{width:100%;border-collapse:collapse;font-size:13.5px;}
th{text-align:left;padding:9px 10px;background:#f9fafb;color:#374151;font-weight:600;border-bottom:1px solid var(--border);}
td{padding:9px 10px;border-bottom:1px solid #f1f1f5;}
tr:hover td{background:#fafafe;}
.badge{padding:3px 9px;border-radius:12px;font-size:11px;font-weight:700;text-transform:capitalize;}
.badge-draft,.badge-trial,.badge-pending{background:#e0e7ff;color:#4338ca;}
.badge-active,.badge-paid,.badge-received,.badge-won,.badge-confirmed,.badge-issued,.badge-accepted{background:#d1fae5;color:#059669;}
.badge-cancelled,.badge-rejected,.badge-lost,.badge-overdue{background:#fee2e2;color:#dc2626;}
.badge-sent,.badge-partial,.badge-suspended{background:#fef3c7;color:#d97706;}
.toolbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;gap:10px;flex-wrap:wrap;}
.toolbar input[type=text]{padding:8px 12px;border:1px solid var(--border);border-radius:8px;font-size:13px;min-width:220px;}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;z-index:100;padding:20px;}
.modal{background:#fff;border-radius:12px;padding:24px;width:560px;max-width:100%;max-height:88vh;overflow-y:auto;}
.modal h3{margin-top:0;}
.item-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;}
.tabs{display:flex;gap:4px;margin-bottom:16px;border-bottom:1px solid var(--border);}
.tab{padding:10px 16px;cursor:pointer;font-size:13.5px;font-weight:600;color:var(--muted);border-bottom:2px solid transparent;}
.tab.active{color:var(--primary);border-bottom-color:var(--primary);}
.empty-state{text-align:center;padding:40px;color:var(--muted);}
.chat-box{display:flex;flex-direction:column;height:60vh;}
.chat-messages{flex:1;overflow-y:auto;padding:12px;background:#fafafe;border-radius:10px;margin-bottom:12px;}
.chat-msg{max-width:70%;padding:10px 14px;border-radius:12px;margin-bottom:10px;font-size:14px;}
.chat-msg.user{background:var(--primary);color:#fff;margin-left:auto;}
.chat-msg.assistant{background:#fff;border:1px solid var(--border);}
.chat-input{display:flex;gap:8px;}
.chat-input input{flex:1;padding:11px 14px;border:1px solid var(--border);border-radius:8px;}
.stat-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f1f1f5;font-size:14px;}
#toast{position:fixed;bottom:20px;right:20px;background:#1e1b3a;color:#fff;padding:12px 20px;border-radius:8px;display:none;z-index:200;font-size:14px;}
</style>
</head>
<body>

<div id="login-screen">
  <div class="login-card">
    <h1>SoudaCore ERP</h1>
    <p>Sign in to your company workspace</p>
    <form id="login-form">
      <div class="field"><label>Company Subdomain</label><input id="li-tenant" placeholder="e.g. demo (from registration)" required></div>
      <div class="field"><label>Email</label><input id="li-email" type="email" required></div>
      <div class="field"><label>Password</label><input id="li-password" type="password" required></div>
      <button class="btn btn-primary" type="submit">Sign In</button>
    </form>
    <div class="error-text" id="login-error"></div>
    <div style="text-align:center;margin-top:16px;font-size:13px;">
      <a href="#" onclick="toggleRegister();return false;" id="register-toggle">New company? Register here</a>
    </div>
    <form id="register-form" style="display:none;margin-top:14px;">
      <div class="field"><label>Company Legal Name</label><input name="legal_name" required></div>
      <div class="field"><label>Subdomain</label><input name="subdomain" pattern="[a-z0-9-]{3,32}" required placeholder="lowercase, e.g. acme"></div>
      <div class="field"><label>Your Full Name</label><input name="admin_full_name" required></div>
      <div class="field"><label>Your Email</label><input name="admin_email" type="email" required></div>
      <div class="field"><label>Password</label><input name="admin_password" type="password" required minlength="10"></div>
      <button class="btn btn-primary" type="submit">Create Company & Sign Up</button>
      <div class="error-text" id="register-error"></div>
    </form>
  </div>
</div>

<div id="app">
  <div class="layout">
    <div class="sidebar" id="sidebar"></div>
    <div class="main">
      <div class="topbar">
        <h2 id="page-title">Dashboard</h2>
        <div style="display:flex;align-items:center;gap:14px;">
          <span id="user-name" style="font-size:13px;color:var(--muted);"></span>
          <button class="btn btn-sm btn-outline" onclick="logout()">Log out</button>
        </div>
      </div>
      <div class="content" id="content"></div>
    </div>
  </div>
</div>

<div id="toast"></div>

<script>
// ==================== Core: API client, auth, router ====================
const API = '/api/v1';
let token = sessionStorage.getItem('sc_token');
let tenantId = sessionStorage.getItem('sc_tenant');
let currentUser = null;

function toggleRegister() {
  const isLogin = document.getElementById('login-form').style.display !== 'none';
  document.getElementById('login-form').style.display = isLogin ? 'none' : '';
  document.getElementById('register-form').style.display = isLogin ? '' : 'none';
  document.getElementById('register-toggle').textContent = isLogin ? 'Already have a company? Sign in' : 'New company? Register here';
}

document.getElementById('register-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  document.getElementById('register-error').textContent = '';
  const payload = {};
  new FormData(e.target).forEach((v, k) => { payload[k] = v; });
  try {
    const res = await fetch(`${API}/public/tenants/register`, {
      method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(payload),
    });
    const body = await res.json();
    if (!res.ok) throw new Error(body.message || 'Registration failed.');
    document.getElementById('li-tenant').value = payload.subdomain;
    document.getElementById('li-email').value = payload.admin_email;
    toggleRegister();
    toast('Company registered — sign in below.');
  } catch (err) {
    document.getElementById('register-error').textContent = err.message;
  }
});

async function api(path, opts = {}) {
  const res = await fetch(API + path, {
    ...opts,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...(tenantId ? { 'X-Tenant-ID': tenantId } : {}),
      ...(opts.headers || {}),
    },
  });
  if (res.status === 204) return null;
  const body = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg = body.message || (body.error ? body.error : 'Request failed');
    throw new Error(msg);
  }
  return body.data;
}

function toast(msg, isError = false) {
  const el = document.getElementById('toast');
  el.textContent = msg;
  el.style.background = isError ? '#dc2626' : '#1e1b3a';
  el.style.display = 'block';
  setTimeout(() => { el.style.display = 'none'; }, 3200);
}

function can(perm) {
  if (!currentUser || !currentUser.role || !currentUser.role.permissions) return false;
  return currentUser.role.permissions.some(p => p.name === perm);
}

document.getElementById('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const subdomain = document.getElementById('li-tenant').value.trim();
  document.getElementById('login-error').textContent = '';
  try {
    const lookup = await fetch(`${API}/public/tenants/lookup?subdomain=${encodeURIComponent(subdomain)}`, { headers: { Accept: 'application/json' } });
    const lookupBody = await lookup.json();
    if (!lookup.ok) throw new Error(lookupBody.message || 'Company not found for that subdomain.');
    tenantId = lookupBody.data.id;

    const data = await api('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email: document.getElementById('li-email').value, password: document.getElementById('li-password').value }),
    });
    if (data.status === 'otp_required') {
      document.getElementById('login-error').textContent = 'This account requires MFA — use a role without mandatory MFA for the demo (e.g. Sales, Manager).';
      return;
    }
    token = data.access_token;
    sessionStorage.setItem('sc_token', token);
    sessionStorage.setItem('sc_tenant', tenantId);
    currentUser = data.user;
    boot();
  } catch (err) {
    document.getElementById('login-error').textContent = err.message;
  }
});

function logout() {
  sessionStorage.removeItem('sc_token');
  sessionStorage.removeItem('sc_tenant');
  token = null; tenantId = null; currentUser = null;
  document.getElementById('app').style.display = 'none';
  document.getElementById('login-screen').style.display = 'flex';
  location.hash = '';
}

async function boot() {
  try {
    if (!currentUser) currentUser = await api('/me');
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('app').style.display = 'block';
    document.getElementById('user-name').textContent = currentUser.full_name + ' — ' + (currentUser.role ? currentUser.role.name_en : '');
    renderSidebar();
    route();
  } catch (err) {
    logout();
  }
}

// ==================== Sidebar & Router ====================
const NAV = [
  { group: 'Overview', items: [{ key: 'dashboard', label: 'Dashboard' }] },
  { group: 'CRM', items: [
    { key: 'leads', label: 'Leads' }, { key: 'customers', label: 'Customers' }, { key: 'opportunities', label: 'Opportunities' },
  ]},
  { group: 'Sales', items: [
    { key: 'salesDashboard', label: 'Sales Dashboard' },
    { key: 'quotations', label: 'Quotations' }, { key: 'salesOrders', label: 'Sales Orders' },
    { key: 'deliveryNotes', label: 'Delivery Notes' }, { key: 'invoices', label: 'Invoices' },
    { key: 'payments', label: 'Payments' }, { key: 'creditNotes', label: 'Credit Notes' },
    { key: 'salesReturns', label: 'Sales Returns' },
  ]},
  { group: 'Purchase', items: [
    { key: 'purchaseDashboard', label: 'Purchase Dashboard' },
    { key: 'suppliers', label: 'Suppliers' }, { key: 'purchaseOrders', label: 'Purchase Orders' },
    { key: 'supplierBills', label: 'Supplier Bills' }, { key: 'supplierPayments', label: 'Supplier Payments' },
    { key: 'debitNotes', label: 'Debit Notes' }, { key: 'purchaseReturns', label: 'Purchase Returns' },
  ]},
  { group: 'Inventory', items: [
    { key: 'products', label: 'Products' }, { key: 'categories', label: 'Categories' },
    { key: 'units', label: 'Units' }, { key: 'brands', label: 'Brands' },
    { key: 'warehouses', label: 'Warehouses' }, { key: 'stock', label: 'Stock' },
    { key: 'stockTransfers', label: 'Stock Transfers' }, { key: 'stockAdjustments', label: 'Stock Adjustments' },
    { key: 'goodsReceipts', label: 'Goods Receipts' }, { key: 'goodsIssues', label: 'Goods Issues' },
  ]},
  { group: 'Accounting', items: [
    { key: 'chartOfAccounts', label: 'Chart of Accounts' }, { key: 'journalEntries', label: 'Journal Entries' },
    { key: 'incomeStatement', label: 'Income Statement' }, { key: 'balanceSheet', label: 'Balance Sheet' },
  ]},
  { group: 'HR & Payroll', items: [
    { key: 'hrDashboard', label: 'HR Dashboard' }, { key: 'employees', label: 'Employees' },
    { key: 'attendance', label: 'Attendance' }, { key: 'leaveRequests', label: 'Leave Requests' },
    { key: 'payrollRuns', label: 'Payroll Runs' }, { key: 'recruitment', label: 'Recruitment' },
    { key: 'performanceReviews', label: 'Performance Reviews' }, { key: 'myEss', label: 'My Self-Service' },
  ]},
  { group: 'Insights', items: [
    { key: 'executiveDashboard', label: 'Executive Dashboard' }, { key: 'kpiDashboard', label: 'KPI Dashboard' },
    { key: 'reports', label: 'Reports' }, { key: 'crmReports', label: 'CRM Reports' },
    { key: 'cashFlow', label: 'Cash Flow' }, { key: 'vatReport', label: 'VAT Report' },
    { key: 'customReports', label: 'Custom Reports' }, { key: 'scheduledReports', label: 'Scheduled Reports' },
    { key: 'ai', label: 'AI Assistant' },
    { key: 'aiInsights', label: 'AI Insights' },
    { key: 'aiSuggestions', label: 'AI Suggestions' },
    { key: 'aiSettings', label: 'AI Settings' },
    { key: 'aiPrompts', label: 'AI Prompts' },
    { key: 'aiActivityLog', label: 'AI Activity Log' },
  ]},
  { group: 'Administration', items: [
    { key: 'users', label: 'Users' }, { key: 'roles', label: 'Roles' }, { key: 'companySettings', label: 'Company Settings' },
  ]},
];

function renderSidebar() {
  const el = document.getElementById('sidebar');
  let html = '<div class="brand">⬡ SoudaCore</div>';
  NAV.forEach(g => {
    html += `<div class="nav-group">${g.group}</div>`;
    g.items.forEach(i => { html += `<a class="nav-item" data-key="${i.key}" onclick="location.hash='${i.key}'">${i.label}</a>`; });
  });
  el.innerHTML = html;
}

function setActiveNav(key) {
  document.querySelectorAll('.nav-item').forEach(el => el.classList.toggle('active', el.dataset.key === key));
}

const ROUTES = {
  dashboard: renderDashboard,
  leads: () => renderListView(MODULES.leads),
  customers: () => renderListView(MODULES.customers),
  opportunities: () => renderListView(MODULES.opportunities),
  salesDashboard: renderSalesDashboard,
  quotations: () => renderDocList(DOC_MODULES.quotations),
  salesOrders: () => renderDocList(DOC_MODULES.salesOrders),
  deliveryNotes: renderDeliveryNotes,
  invoices: () => renderDocList(DOC_MODULES.invoices),
  payments: renderPayments,
  creditNotes: renderCreditNotes,
  salesReturns: renderSalesReturns,
  purchaseDashboard: renderPurchaseDashboard,
  suppliers: () => renderListView(MODULES.suppliers),
  purchaseOrders: () => renderDocList(DOC_MODULES.purchaseOrders),
  supplierBills: renderSupplierBills,
  supplierPayments: renderSupplierPayments,
  debitNotes: renderDebitNotes,
  purchaseReturns: renderPurchaseReturns,
  products: () => renderListView(MODULES.products),
  categories: () => renderListView(MODULES.categories),
  units: () => renderListView(MODULES.units),
  brands: () => renderListView(MODULES.brands),
  warehouses: () => renderListView(MODULES.warehouses),
  stock: renderStock,
  stockTransfers: renderStockTransfers,
  stockAdjustments: renderStockAdjustments,
  goodsReceipts: renderGoodsReceipts,
  goodsIssues: renderGoodsIssues,
  chartOfAccounts: () => renderListView(MODULES.chartOfAccounts),
  journalEntries: renderJournalEntries,
  incomeStatement: renderIncomeStatement,
  balanceSheet: renderBalanceSheet,
  hrDashboard: renderHrDashboard,
  employees: renderEmployees,
  attendance: renderAttendance,
  leaveRequests: renderLeaveRequests,
  payrollRuns: renderPayrollRuns,
  recruitment: renderRecruitment,
  performanceReviews: renderPerformanceReviews,
  myEss: renderMyEss,
  reports: renderReports,
  executiveDashboard: renderExecutiveDashboard,
  kpiDashboard: renderKpiDashboard,
  crmReports: renderCrmReports,
  cashFlow: renderCashFlow,
  vatReport: renderVatReport,
  customReports: renderCustomReports,
  scheduledReports: renderScheduledReports,
  ai: renderAiAssistant,
  aiInsights: renderAiInsights,
  aiSuggestions: renderAiSuggestions,
  aiSettings: renderAiSettings,
  aiPrompts: renderAiPrompts,
  aiActivityLog: renderAiActivityLog,
  users: renderUsers,
  roles: renderRoles,
  companySettings: renderCompanySettings,
};

async function route() {
  const key = (location.hash || '#dashboard').slice(1);
  setActiveNav(key);
  document.getElementById('page-title').textContent = (NAV.flatMap(g => g.items).find(i => i.key === key) || { label: 'Dashboard' }).label;
  const fn = ROUTES[key] || renderDashboard;
  document.getElementById('content').innerHTML = '<div class="empty-state">Loading…</div>';
  try {
    await fn();
  } catch (err) {
    document.getElementById('content').innerHTML = `<div class="card error-text">Failed to load: ${err.message}</div>`;
  }
}
window.addEventListener('hashchange', route);

// ==================== Dashboard ====================
async function renderDashboard() {
  const [platformDash, crmDash, salesRpt, purchaseRpt, invRpt] = await Promise.all([
    api('/dashboard').catch(() => null),
    api('/crm/dashboard').catch(() => null),
    api('/reports/sales').catch(() => null),
    api('/reports/purchases').catch(() => null),
    api('/reports/inventory').catch(() => null),
  ]);

  let html = '<div class="grid grid-4">';
  const metric = (label, value) => `<div class="card"><div class="metric-value">${value}</div><div class="metric-label">${label}</div></div>`;
  if (crmDash) {
    html += metric('Open Leads', crmDash.totals.total_leads - (crmDash.totals.won_this_month||0));
    html += metric('Customers', crmDash.customers.total_customers);
    html += metric('Open Opportunities', crmDash.opportunities.total_open);
    html += metric('Weighted Pipeline (SAR)', Number(crmDash.opportunities.open_pipeline_value).toLocaleString());
  }
  if (salesRpt) {
    html += metric('Invoiced Total (SAR)', Number(salesRpt.total_invoiced).toLocaleString());
    html += metric('Collected (SAR)', Number(salesRpt.total_collected).toLocaleString());
    html += metric('Outstanding (SAR)', Number(salesRpt.total_outstanding).toLocaleString());
  }
  if (purchaseRpt) html += metric('Purchase Orders Total (SAR)', Number(purchaseRpt.total_ordered).toLocaleString());
  if (invRpt) {
    html += metric('Products', invRpt.total_products);
    html += metric('Stock Value (SAR)', Number(invRpt.total_stock_value).toLocaleString());
    html += metric('Low Stock Items', invRpt.low_stock_products);
  }
  if (platformDash) {
    html += metric('Subscription Status', platformDash.widgets.subscription_status.status);
  }
  html += '</div>';

  if (platformDash && platformDash.recent_activities && platformDash.recent_activities.data && platformDash.recent_activities.data.length) {
    html += '<div class="card"><h3>Recent Activity</h3>';
    platformDash.recent_activities.data.slice(0, 8).forEach(a => {
      html += `<div class="stat-row"><span>${a.description || a.event}</span><span style="color:var(--muted);font-size:12px;">${new Date(a.created_at).toLocaleString()}</span></div>`;
    });
    html += '</div>';
  }

  document.getElementById('content').innerHTML = html;
}

// ==================== Generic simple-entity list view ====================
function fmtDate(v) { return v ? new Date(v).toLocaleDateString() : '—'; }
function fmtMoney(v) { return v === null || v === undefined ? '—' : Number(v).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}); }
function badge(status) { return status ? `<span class="badge badge-${status}">${status}</span>` : ''; }
// Dependency-free inline SVG bar chart — no chart library/CDN needed, keeps the console a single self-contained file.
function svgBarChart(items, labelKey, valueKey, opts = {}) {
  const w = opts.width || 560, h = opts.height || 220, pad = 32, barGap = 10;
  const max = Math.max(1, ...items.map(i => Number(i[valueKey]) || 0));
  const barW = items.length ? (w - pad * 2) / items.length - barGap : 0;
  let bars = '', labels = '';
  items.forEach((item, i) => {
    const val = Number(item[valueKey]) || 0;
    const barH = (val / max) * (h - pad * 2);
    const x = pad + i * (barW + barGap);
    const y = h - pad - barH;
    bars += `<rect x="${x}" y="${y}" width="${barW}" height="${barH}" fill="var(--primary,#3B82F6)" rx="2"></rect>
      <text x="${x + barW/2}" y="${y - 4}" font-size="10" text-anchor="middle" fill="var(--text-muted,#666)">${fmtMoney(val)}</text>`;
    labels += `<text x="${x + barW/2}" y="${h - pad + 14}" font-size="10" text-anchor="middle" fill="var(--text-muted,#666)">${String(item[labelKey]).slice(0,12)}</text>`;
  });
  return `<svg width="100%" viewBox="0 0 ${w} ${h}" style="max-width:${w}px;">${bars}${labels}</svg>`;
}

async function renderListView(cfg) {
  const el = document.getElementById('content');
  const data = await api(cfg.endpoint + '?page_size=50');
  const rows = Array.isArray(data) ? data : (data.data || data);

  let html = `<div class="card"><div class="toolbar">
      <input type="text" placeholder="Search…" id="search-box" oninput="filterTable(this.value)">
      ${cfg.createFields ? `<button class="btn btn-primary btn-sm" onclick="openCreateModal('${cfg.key}')">+ New ${cfg.singular}</button>` : ''}
    </div>
    <table id="data-table"><thead><tr>${cfg.columns.map(c => `<th>${c.label}</th>`).join('')}<th></th></tr></thead><tbody>`;

  if (!rows.length) {
    html += `<tr><td colspan="${cfg.columns.length + 1}"><div class="empty-state">No ${cfg.singular.toLowerCase()} records yet.</div></td></tr>`;
  }
  rows.forEach(row => {
    html += '<tr>' + cfg.columns.map(c => `<td>${c.fmt ? c.fmt(row) : (row[c.key] ?? '—')}</td>`).join('') +
      `<td>${cfg.rowActions ? cfg.rowActions(row) : ''}</td></tr>`;
  });
  html += '</tbody></table></div>';
  el.innerHTML = html;
  window.__currentRows = rows;
}

function filterTable(term) {
  term = term.toLowerCase();
  document.querySelectorAll('#data-table tbody tr').forEach(tr => {
    tr.style.display = tr.textContent.toLowerCase().includes(term) ? '' : 'none';
  });
}

function closeModal() { const m = document.querySelector('.modal-overlay'); if (m) m.remove(); }

async function openCreateModal(moduleKey) {
  const cfg = MODULES[moduleKey];
  const optionsCache = {};
  for (const f of cfg.createFields) {
    if (f.optionsFrom) optionsCache[f.name] = await api(f.optionsFrom + '?page_size=100');
  }
  const fieldsHtml = cfg.createFields.map(f => {
    if (f.type === 'select' && f.optionsFrom) {
      const opts = (optionsCache[f.name] || []).map(o => `<option value="${o.id}">${f.optionLabel(o)}</option>`).join('');
      return `<div class="field"><label>${f.label}</label><select name="${f.name}">${opts}</select></div>`;
    }
    if (f.type === 'select' && f.options) {
      const opts = f.options.map(o => `<option value="${o}">${o}</option>`).join('');
      return `<div class="field"><label>${f.label}</label><select name="${f.name}">${opts}</select></div>`;
    }
    return `<div class="field"><label>${f.label}</label><input name="${f.name}" type="${f.type||'text'}" ${f.required?'required':''}></div>`;
  }).join('');

  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New ${cfg.singular}</h3><form id="create-form">${fieldsHtml}
    <div style="margin-top:16px;display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);

  document.getElementById('create-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {};
    new FormData(e.target).forEach((v, k) => { payload[k] = v; });
    try {
      await api(cfg.endpoint, { method: 'POST', body: JSON.stringify(payload) });
      closeModal();
      toast(cfg.singular + ' created.');
      route();
    } catch (err) {
      document.getElementById('modal-error').textContent = err.message;
    }
  });
}

// ==================== Module configs (simple entities) ====================
const MODULES = {
  leads: {
    key: 'leads', singular: 'Lead', endpoint: '/crm/leads',
    columns: [
      { key: 'lead_number', label: '#' },
      { key: 'name', label: 'Name', fmt: r => r.full_name || r.first_name },
      { key: 'company_name', label: 'Company' },
      { key: 'status', label: 'Status', fmt: r => badge(r.status ? r.status.name_en : '') },
      { key: 'priority', label: 'Priority', fmt: r => badge(r.priority) },
      { key: 'assignee', label: 'Assigned To', fmt: r => r.assignee ? r.assignee.full_name : '—' },
    ],
    createFields: [
      { name: 'first_name', label: 'First Name', required: true },
      { name: 'last_name', label: 'Last Name' },
      { name: 'company_name', label: 'Company Name' },
      { name: 'email', label: 'Email', type: 'email' },
      { name: 'phone', label: 'Phone' },
      { name: 'expected_revenue', label: 'Expected Revenue (SAR)', type: 'number' },
    ],
  },
  customers: {
    key: 'customers', singular: 'Customer', endpoint: '/crm/customers',
    columns: [
      { key: 'customer_number', label: '#' },
      { key: 'name', label: 'Name', fmt: r => r.full_name },
      { key: 'company_name', label: 'Company' },
      { key: 'status', label: 'Status', fmt: r => badge(r.status) },
      { key: 'manager', label: 'Account Manager', fmt: r => r.account_manager ? r.account_manager.full_name : '—' },
    ],
    createFields: [
      { name: 'first_name', label: 'First Name', required: true },
      { name: 'last_name', label: 'Last Name' },
      { name: 'company_name', label: 'Company Name' },
      { name: 'email', label: 'Email', type: 'email' },
      { name: 'phone', label: 'Phone' },
    ],
  },
  opportunities: {
    key: 'opportunities', singular: 'Opportunity', endpoint: '/crm/opportunities',
    columns: [
      { key: 'opportunity_number', label: '#' },
      { key: 'name', label: 'Deal' },
      { key: 'customer', label: 'Customer', fmt: r => r.customer ? r.customer.full_name : '—' },
      { key: 'stage', label: 'Stage', fmt: r => r.stage ? `<span class="badge" style="background:${r.stage.color}22;color:${r.stage.color}">${r.stage.name_en}</span>` : '—' },
      { key: 'amount', label: 'Amount', fmt: r => fmtMoney(r.amount) },
      { key: 'weighted_value', label: 'Weighted', fmt: r => fmtMoney(r.weighted_value) },
    ],
  },
  suppliers: {
    key: 'suppliers', singular: 'Supplier', endpoint: '/purchase/suppliers',
    columns: [
      { key: 'supplier_number', label: '#' }, { key: 'name', label: 'Name' }, { key: 'email', label: 'Email' },
      { key: 'phone', label: 'Phone' }, { key: 'payment_terms_days', label: 'Terms (days)' },
    ],
    createFields: [
      { name: 'name', label: 'Name', required: true },
      { name: 'email', label: 'Email', type: 'email' },
      { name: 'phone', label: 'Phone' },
      { name: 'payment_terms_days', label: 'Payment Terms (days)', type: 'number' },
    ],
  },
  products: {
    key: 'products', singular: 'Product', endpoint: '/inventory/products',
    columns: [
      { key: 'sku', label: 'SKU' }, { key: 'barcode', label: 'Barcode', fmt: r => r.barcode || '—' },
      { key: 'name_en', label: 'Name' },
      { key: 'brand', label: 'Brand', fmt: r => r.brand ? r.brand.name : '—' },
      { key: 'cost_price', label: 'Cost', fmt: r => fmtMoney(r.cost_price) },
      { key: 'sale_price', label: 'Price', fmt: r => fmtMoney(r.sale_price) },
      { key: 'total_stock', label: 'Stock', fmt: r => (r.is_low_stock ? '⚠️ ' : '') + (r.total_stock ?? '—') },
    ],
    createFields: [
      { name: 'sku', label: 'SKU', required: true },
      { name: 'barcode', label: 'Barcode' },
      { name: 'name_en', label: 'Name', required: true },
      { name: 'category_id', label: 'Category', type: 'select', optionsFrom: '/inventory/categories', optionLabel: o => o.name_en },
      { name: 'unit_id', label: 'Unit', type: 'select', optionsFrom: '/inventory/units', optionLabel: o => o.name_en },
      { name: 'brand_id', label: 'Brand', type: 'select', optionsFrom: '/inventory/brands', optionLabel: o => o.name },
      { name: 'cost_price', label: 'Cost Price (SAR)', type: 'number' },
      { name: 'sale_price', label: 'Sale Price (SAR)', type: 'number' },
      { name: 'reorder_point', label: 'Reorder Point', type: 'number' },
    ],
  },
  categories: {
    key: 'categories', singular: 'Category', endpoint: '/inventory/categories',
    columns: [{ key: 'name_en', label: 'Name' }, { key: 'name_ar', label: 'Arabic Name', fmt: r => r.name_ar || '—' }],
    createFields: [{ name: 'name_en', label: 'Name (English)', required: true }, { name: 'name_ar', label: 'Name (Arabic)' }],
  },
  units: {
    key: 'units', singular: 'Unit', endpoint: '/inventory/units',
    columns: [{ key: 'code', label: 'Code' }, { key: 'name_en', label: 'Name' }],
    createFields: [{ name: 'code', label: 'Code', required: true }, { name: 'name_en', label: 'Name', required: true }],
  },
  brands: {
    key: 'brands', singular: 'Brand', endpoint: '/inventory/brands',
    columns: [{ key: 'name', label: 'Name' }],
    createFields: [{ name: 'name', label: 'Name', required: true }],
  },
  warehouses: {
    key: 'warehouses', singular: 'Warehouse', endpoint: '/inventory/warehouses',
    columns: [
      { key: 'name', label: 'Name' },
      { key: 'is_default', label: 'Default', fmt: r => r.is_default ? '✓' : '' },
      { key: 'is_active', label: 'Status', fmt: r => badge(r.is_active ? 'active' : 'inactive') },
    ],
    createFields: [{ name: 'name', label: 'Name', required: true }],
  },
  chartOfAccounts: {
    key: 'chartOfAccounts', singular: 'Account', endpoint: '/accounting/chart-of-accounts',
    columns: [
      { key: 'code', label: 'Code' }, { key: 'name_en', label: 'Name' }, { key: 'type', label: 'Type', fmt: r => badge(r.type) },
    ],
    createFields: [
      { name: 'code', label: 'Code', required: true },
      { name: 'name_en', label: 'Name', required: true },
      { name: 'type', label: 'Type', type: 'select', options: ['asset','liability','equity','revenue','expense'] },
    ],
  },
};

// ==================== Document modules (Quotation/SalesOrder/Invoice/PO — have line items + workflow actions) ====================
const DOC_MODULES = {
  quotations: {
    key: 'quotations', singular: 'Quotation', endpoint: '/sales/quotations', partySelector: 'customer_id', partyEndpoint: '/crm/customers', priceField: 'unit_price',
    columns: [
      { key: 'document_number', label: '#' }, { key: 'customer', label: 'Customer', fmt: r => r.customer ? r.customer.full_name : '—' },
      { key: 'status', label: 'Status', fmt: r => badge(r.status) }, { key: 'total', label: 'Total', fmt: r => fmtMoney(r.total) },
      { key: 'document_date', label: 'Date', fmt: r => fmtDate(r.document_date) },
    ],
    actions: (r) => r.status === 'accepted' ? [{ label: 'Convert to Order', fn: () => convertDoc('quotations', r.id, 'convert-to-order', 'Sales Order') }] : [],
    statusOptions: ['draft','sent','accepted','rejected','expired'],
  },
  salesOrders: {
    key: 'salesOrders', singular: 'Sales Order', endpoint: '/sales/orders', partySelector: 'customer_id', partyEndpoint: '/crm/customers', priceField: 'unit_price',
    columns: [
      { key: 'document_number', label: '#' }, { key: 'customer', label: 'Customer', fmt: r => r.customer ? r.customer.full_name : '—' },
      { key: 'status', label: 'Status', fmt: r => badge(r.status) }, { key: 'total', label: 'Total', fmt: r => fmtMoney(r.total) },
      { key: 'document_date', label: 'Date', fmt: r => fmtDate(r.document_date) },
    ],
    actions: (r) => r.status === 'confirmed' ? [
      { label: 'Convert to Invoice', fn: () => convertDoc('salesOrders', r.id, 'convert-to-invoice', 'Invoice') },
      { label: 'Deliver', fn: () => createDeliveryFromOrder(r.id) },
    ] : [],
    statusOptions: ['draft','confirmed','fulfilled','cancelled'],
  },
  invoices: {
    key: 'invoices', singular: 'Invoice', endpoint: '/sales/invoices', partySelector: 'customer_id', partyEndpoint: '/crm/customers', priceField: 'unit_price',
    columns: [
      { key: 'document_number', label: '#' }, { key: 'customer', label: 'Customer', fmt: r => r.customer ? r.customer.full_name : '—' },
      { key: 'status', label: 'Status', fmt: r => badge(r.status) }, { key: 'total', label: 'Total', fmt: r => fmtMoney(r.total) },
      { key: 'balance_due', label: 'Balance Due', fmt: r => fmtMoney(r.balance_due) },
    ],
    actions: (r) => {
      const a = [];
      if (r.status === 'draft') a.push({ label: 'Issue', fn: () => simpleAction('/sales/invoices/' + r.id + '/issue', 'Invoice issued.') });
      if (['issued','partial'].includes(r.status)) a.push({ label: 'Record Payment', fn: () => recordPayment(r) });
      return a;
    },
  },
  purchaseOrders: {
    key: 'purchaseOrders', singular: 'Purchase Order', endpoint: '/purchase/orders', partySelector: 'supplier_id', partyEndpoint: '/purchase/suppliers', priceField: 'unit_cost',
    columns: [
      { key: 'po_number', label: '#' }, { key: 'supplier', label: 'Supplier', fmt: r => r.supplier ? r.supplier.name : '—' },
      { key: 'status', label: 'Status', fmt: r => badge(r.status) }, { key: 'total', label: 'Total', fmt: r => fmtMoney(r.total) },
      { key: 'order_date', label: 'Date', fmt: r => fmtDate(r.order_date) },
    ],
    actions: (r) => r.status !== 'received' ? [{ label: 'Receive', fn: () => simpleAction('/purchase/orders/' + r.id + '/receive', 'Purchase order received — stock updated.') }] : [],
  },
};

async function renderDocList(cfg) {
  const el = document.getElementById('content');
  const data = await api(cfg.endpoint + '?page_size=50');
  const rows = data.data || data;
  window.__docActions.length = 0; // reset registry each render — stale indices from a prior screen must never be reachable

  let html = `<div class="card"><div class="toolbar">
    <input type="text" placeholder="Search…" oninput="filterTable(this.value)">
    <button class="btn btn-primary btn-sm" onclick="openDocModal('${cfg.key}')">+ New ${cfg.singular}</button>
  </div><table id="data-table"><thead><tr>${cfg.columns.map(c=>`<th>${c.label}</th>`).join('')}<th>Actions</th></tr></thead><tbody>`;

  if (!rows.length) html += `<tr><td colspan="${cfg.columns.length+1}"><div class="empty-state">No ${cfg.singular.toLowerCase()} records yet.</div></td></tr>`;

  rows.forEach(r => {
    const actions = (cfg.actions ? cfg.actions(r) : []).map((a, i) =>
      `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(a.fn)-1}]()'>${a.label}</button>`
    ).join(' ');
    html += '<tr>' + cfg.columns.map(c => `<td>${c.fmt ? c.fmt(r) : (r[c.key] ?? '—')}</td>`).join('') + `<td>${actions}</td></tr>`;
  });
  html += '</tbody></table></div>';
  el.innerHTML = html;
}
window.__docActions = [];
window.docActionRegistry = window.__docActions;

async function simpleAction(path, successMsg) {
  try {
    await api(path, { method: 'POST' });
    toast(successMsg);
    route();
  } catch (err) { toast(err.message, true); }
}

async function convertDoc(moduleKey, id, action, targetLabel) {
  const cfg = DOC_MODULES[moduleKey];
  try {
    await api(`${cfg.endpoint}/${id}/${action}`, { method: 'POST' });
    toast(`Converted to ${targetLabel}.`);
    route();
  } catch (err) { toast(err.message, true); }
}

async function recordPayment(invoice) {
  const amount = prompt(`Record payment for ${invoice.document_number} (balance due: ${fmtMoney(invoice.balance_due)} SAR):`);
  if (!amount) return;
  try {
    await api(`/sales/invoices/${invoice.id}/record-payment`, { method: 'POST', body: JSON.stringify({ amount: parseFloat(amount) }) });
    toast('Payment recorded.');
    route();
  } catch (err) { toast(err.message, true); }
}

async function openDocModal(moduleKey) {
  const cfg = DOC_MODULES[moduleKey];
  const [parties, products] = await Promise.all([
    api(cfg.partyEndpoint + '?page_size=100'),
    api('/inventory/products?page_size=100'),
  ]);
  const partyOpts = parties.map(p => `<option value="${p.id}">${p.full_name || p.name}</option>`).join('');
  const productOpts = products.map(p => `<option value="${p.id}" data-price="${p.sale_price}" data-cost="${p.cost_price}">${p.sku} — ${p.name_en}</option>`).join('');

  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal">
    <h3>New ${cfg.singular}</h3>
    <form id="doc-form">
      <div class="field"><label>${cfg.partySelector === 'customer_id' ? 'Customer' : 'Supplier'}</label>
        <select name="${cfg.partySelector}" required>${partyOpts}</select></div>
      <div class="field"><label>Line Items</label><div id="item-rows"></div>
        <button type="button" class="btn btn-sm btn-outline" onclick="addItemRow('${moduleKey}')">+ Add Line</button></div>
      <div style="text-align:right;font-weight:700;margin:10px 0;" id="doc-total">Total: 0.00 SAR</div>
      <div style="display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
      <div class="error-text" id="modal-error"></div>
    </form></div>`;
  document.body.appendChild(overlay);
  window.__productOpts = productOpts;
  addItemRow(moduleKey);

  document.getElementById('doc-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const items = [...document.querySelectorAll('.item-row')].map(row => ({
      product_id: row.querySelector('.item-product').value,
      quantity: parseFloat(row.querySelector('.item-qty').value),
      [cfg.priceField]: parseFloat(row.querySelector('.item-price').value),
    })).filter(i => i.product_id && i.quantity > 0);

    if (!items.length) { document.getElementById('modal-error').textContent = 'Add at least one line item.'; return; }

    const payload = { [cfg.partySelector]: form[cfg.partySelector].value, items };
    try {
      await api(cfg.endpoint, { method: 'POST', body: JSON.stringify(payload) });
      closeModal();
      toast(cfg.singular + ' created.');
      route();
    } catch (err) {
      document.getElementById('modal-error').textContent = err.message;
    }
  });
}

function addItemRow(moduleKey) {
  const cfg = DOC_MODULES[moduleKey];
  const container = document.getElementById('item-rows');
  const row = document.createElement('div');
  row.className = 'item-row';
  row.innerHTML = `
    <select class="item-product">${window.__productOpts}</select>
    <input class="item-qty" type="number" placeholder="Qty" value="1" min="0.001" step="0.001">
    <input class="item-price" type="number" placeholder="${cfg.priceField === 'unit_cost' ? 'Cost' : 'Price'}" step="0.01">
    <span></span>
    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.item-row').remove(); recalcDocTotal('${moduleKey}')">×</button>`;
  container.appendChild(row);

  const priceInput = row.querySelector('.item-price');
  const productSelect = row.querySelector('.item-product');
  const applyDefaultPrice = () => {
    const opt = productSelect.selectedOptions[0];
    if (opt) priceInput.value = cfg.priceField === 'unit_cost' ? opt.dataset.cost : opt.dataset.price;
    recalcDocTotal(moduleKey);
  };
  productSelect.addEventListener('change', applyDefaultPrice);
  row.querySelector('.item-qty').addEventListener('input', () => recalcDocTotal(moduleKey));
  priceInput.addEventListener('input', () => recalcDocTotal(moduleKey));
  applyDefaultPrice();
}

function recalcDocTotal(moduleKey) {
  let subtotal = 0;
  document.querySelectorAll('.item-row').forEach(row => {
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const price = parseFloat(row.querySelector('.item-price').value) || 0;
    subtotal += qty * price;
  });
  const vat = subtotal * 0.15;
  document.getElementById('doc-total').textContent = `Subtotal: ${subtotal.toFixed(2)} + VAT: ${vat.toFixed(2)} = Total: ${(subtotal+vat).toFixed(2)} SAR`;
}

// ==================== Sales Dashboard ====================
async function renderSalesDashboard() {
  const d = await api('/sales/dashboard');
  const metric = (label, value) => `<div class="card"><div class="metric-value">${value}</div><div class="metric-label">${label}</div></div>`;
  let html = '<div class="grid grid-4">';
  html += metric('Quotations', d.document_counts.quotations);
  html += metric('Sales Orders', d.document_counts.sales_orders);
  html += metric('Invoices', d.document_counts.invoices);
  html += metric('Credit Notes', d.document_counts.credit_notes);
  html += metric('Quotation Win Rate', d.quotation_conversion_rate + '%');
  html += metric('Revenue This Month', fmtMoney(d.revenue_this_month) + ' SAR');
  html += metric('Payments This Month', fmtMoney(d.payments_this_month) + ' SAR');
  html += metric('Outstanding Receivables', fmtMoney(d.outstanding_receivables) + ' SAR');
  html += metric('Overdue Invoices', d.overdue_invoices);
  html += '</div>';

  const aging = await api('/reports/aging-receivables');
  html += `<div class="card"><h3>Receivables Aging</h3><div class="grid grid-4">
    <div><div class="metric-value">${fmtMoney(aging.current)}</div><div class="metric-label">Current</div></div>
    <div><div class="metric-value">${fmtMoney(aging.days_1_30)}</div><div class="metric-label">1-30 Days</div></div>
    <div><div class="metric-value">${fmtMoney(aging.days_31_60)}</div><div class="metric-label">31-60 Days</div></div>
    <div><div class="metric-value">${fmtMoney(aging.days_90_plus)}</div><div class="metric-label">90+ Days</div></div>
  </div></div>`;
  document.getElementById('content').innerHTML = html;
}

// ==================== Delivery Notes ====================
async function createDeliveryFromOrder(orderId) {
  try {
    await api(`/sales/orders/${orderId}/deliver`, { method: 'POST' });
    toast('Delivery note created — go to Delivery Notes to confirm delivery.');
    route();
  } catch (err) { toast(err.message, true); }
}

async function renderDeliveryNotes() {
  const data = await api('/sales/delivery-notes?page_size=50');
  const rows = data.data || data;
  let html = '<div class="card"><table><thead><tr><th>#</th><th>Customer</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>';
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;
  if (!rows.length) html += '<tr><td colspan="5"><div class="empty-state">No delivery notes yet — deliver a confirmed Sales Order to create one.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/sales/delivery-notes/"+r.id+"/deliver","Delivered — stock updated."))-1}]()'>Deliver</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.customer?r.customer.full_name:'—'}</td><td>${badge(r.status)}</td><td>${fmtDate(r.document_date)}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Payments ====================
async function renderPayments() {
  const data = await api('/sales/payments?page_size=50');
  const rows = data.data || data;
  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openPaymentModal()">+ Record Payment</button></div>
    <table><thead><tr><th>#</th><th>Customer</th><th>Amount</th><th>Allocated</th><th>Unallocated</th><th>Method</th><th>Date</th></tr></thead><tbody>`;
  if (!rows.length) html += '<tr><td colspan="7"><div class="empty-state">No payments recorded yet.</div></td></tr>';
  rows.forEach(r => {
    html += `<tr><td>${r.payment_number}</td><td>${r.customer?r.customer.full_name:'—'}</td><td>${fmtMoney(r.amount)}</td><td>${fmtMoney(r.allocated_amount)}</td><td>${fmtMoney(r.unallocated_amount)}</td><td>${r.payment_method}</td><td>${fmtDate(r.payment_date)}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function openPaymentModal() {
  const customers = await api('/crm/customers?page_size=100');
  const custOpts = customers.map(c => `<option value="${c.id}">${c.full_name}</option>`).join('');
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>Record Payment</h3><form id="pay-form">
    <div class="field"><label>Customer</label><select name="customer_id">${custOpts}</select></div>
    <div class="field"><label>Amount (SAR)</label><input name="amount" type="number" step="0.01" required></div>
    <div class="field"><label>Method</label><select name="payment_method"><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
    <div class="field"><label>Reference</label><input name="reference"></div>
    <p style="font-size:12px;color:var(--muted);">This records an unallocated payment. Use "Record Payment" on a specific invoice to pay it directly, or allocate this payment afterward via the API.</p>
    <div style="display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Record</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  document.getElementById('pay-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {}; new FormData(e.target).forEach((v,k) => payload[k]=v);
    try { await api('/sales/payments', { method: 'POST', body: JSON.stringify(payload) }); closeModal(); toast('Payment recorded.'); route(); }
    catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== Credit Notes ====================
async function renderCreditNotes() {
  const data = await api('/sales/credit-notes?page_size=50');
  const rows = data.data || data;
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;
  let html = '<div class="card"><table><thead><tr><th>#</th><th>Customer</th><th>Status</th><th>Total</th><th>Reason</th><th>Actions</th></tr></thead><tbody>';
  if (!rows.length) html += '<tr><td colspan="6"><div class="empty-state">No credit notes yet — issue one from an Invoice, or receive a Sales Return to auto-generate one.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/sales/credit-notes/"+r.id+"/issue","Credit note issued."))-1}]()'>Issue</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.customer?r.customer.full_name:'—'}</td><td>${badge(r.status)}</td><td>${fmtMoney(r.total)}</td><td>${r.reason||'—'}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Sales Returns ====================
async function renderSalesReturns() {
  const data = await api('/sales/returns?page_size=50');
  const rows = data.data || data;
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;
  let html = '<div class="card"><table><thead><tr><th>#</th><th>Customer</th><th>Status</th><th>Reason</th><th>Credit Note</th><th>Actions</th></tr></thead><tbody>';
  if (!rows.length) html += '<tr><td colspan="6"><div class="empty-state">No sales returns yet.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/sales/returns/"+r.id+"/receive","Return received — stock and credit note updated."))-1}]()'>Receive</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.customer?r.customer.full_name:'—'}</td><td>${badge(r.status)}</td><td>${r.reason||'—'}</td><td>${r.credit_note_id?'Yes':'—'}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Stock ====================
async function renderStock() {
  const [levels, movements] = await Promise.all([
    api('/inventory/stock-levels?page_size=100'), api('/inventory/stock-movements?page_size=20'),
  ]);
  let html = '<div class="tabs"><div class="tab active" onclick="showStockTab(\'levels\')" id="tab-levels">Stock Levels</div><div class="tab" onclick="showStockTab(\'movements\')" id="tab-movements">Recent Movements</div></div>';
  html += '<div id="stock-levels" class="card"><table><thead><tr><th>Product</th><th>Warehouse</th><th>Quantity</th></tr></thead><tbody>';
  (levels.data || levels).forEach(l => { html += `<tr><td>${l.product ? l.product.sku + ' — ' + l.product.name_en : '—'}</td><td>${l.warehouse ? l.warehouse.name : '—'}</td><td>${l.quantity}</td></tr>`; });
  html += '</tbody></table></div>';
  html += '<div id="stock-movements" class="card" style="display:none;"><table><thead><tr><th>Type</th><th>Product</th><th>Qty</th><th>Reference</th><th>Date</th></tr></thead><tbody>';
  (movements.data || movements).forEach(m => { html += `<tr><td>${badge(m.type)}</td><td>${m.product?m.product.sku:'—'}</td><td>${m.quantity}</td><td>${m.reference_type||'—'}</td><td>${fmtDate(m.created_at)}</td></tr>`; });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}
function showStockTab(which) {
  document.getElementById('stock-levels').style.display = which === 'levels' ? '' : 'none';
  document.getElementById('stock-movements').style.display = which === 'movements' ? '' : 'none';
  document.getElementById('tab-levels').classList.toggle('active', which === 'levels');
  document.getElementById('tab-movements').classList.toggle('active', which === 'movements');
}

// ==================== Journal Entries (custom — debit/credit line builder) ====================
async function renderJournalEntries() {
  const [entries, accounts] = await Promise.all([api('/accounting/journal-entries?page_size=30'), api('/accounting/chart-of-accounts?page_size=100')]);
  window.__coaOptions = accounts.map(a => `<option value="${a.id}">${a.code} — ${a.name_en}</option>`).join('');
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openJournalModal()">+ New Journal Entry</button></div>
    <table><thead><tr><th>#</th><th>Date</th><th>Memo</th><th>Debit</th><th>Credit</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
  (entries.data || entries).forEach(e => {
    const d = (e.lines||[]).reduce((s,l)=>s+l.debit,0), c = (e.lines||[]).reduce((s,l)=>s+l.credit,0);
    let status = e.is_reversed ? badge('reversed') : (e.source_type ? `<span style="font-size:12px;color:var(--text-muted);">Auto (${e.source_type})</span>` : badge('manual'));
    let action = '';
    if (!e.is_reversed && !e.source_type) {
      action = `<button class="btn btn-sm btn-outline" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/accounting/journal-entries/"+e.id+"/reverse","Reversal entry posted."))-1}]()'>Reverse</button>`;
    }
    html += `<tr><td>${e.entry_number}</td><td>${fmtDate(e.entry_date)}</td><td>${e.memo||'—'}</td><td>${fmtMoney(d)}</td><td>${fmtMoney(c)}</td><td>${status}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

function openJournalModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Journal Entry</h3><form id="je-form">
    <div class="field"><label>Memo</label><input name="memo"></div>
    <div id="je-lines"></div>
    <button type="button" class="btn btn-sm btn-outline" onclick="addJeLine()">+ Add Line</button>
    <div style="margin-top:16px;display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Post Entry</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  addJeLine(); addJeLine();

  document.getElementById('je-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const lines = [...document.querySelectorAll('.je-line')].map(row => ({
      account_id: row.querySelector('.je-account').value,
      debit: parseFloat(row.querySelector('.je-debit').value) || 0,
      credit: parseFloat(row.querySelector('.je-credit').value) || 0,
    }));
    try {
      await api('/accounting/journal-entries', { method: 'POST', body: JSON.stringify({ memo: e.target.memo.value, lines }) });
      closeModal(); toast('Journal entry posted.'); route();
    } catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}
function addJeLine() {
  const row = document.createElement('div');
  row.className = 'item-row je-line';
  row.innerHTML = `<select class="je-account">${window.__coaOptions}</select>
    <input class="je-debit" type="number" placeholder="Debit" step="0.01">
    <input class="je-credit" type="number" placeholder="Credit" step="0.01"><span></span>
    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.je-line').remove()">×</button>`;
  document.getElementById('je-lines').appendChild(row);
}

// ==================== Reports ====================
async function renderReports() {
  const [sales, purchases, inventory, trial] = await Promise.all([
    api('/reports/sales'), api('/reports/purchases'), api('/reports/inventory'), api('/reports/trial-balance'),
  ]);
  let html = '<div class="grid grid-2">';
  html += `<div class="card"><h3>Sales</h3>
    <div class="stat-row"><span>Total Invoiced</span><span>${fmtMoney(sales.total_invoiced)} SAR</span></div>
    <div class="stat-row"><span>Total Collected</span><span>${fmtMoney(sales.total_collected)} SAR</span></div>
    <div class="stat-row"><span>Outstanding</span><span>${fmtMoney(sales.total_outstanding)} SAR</span></div>
    <div class="stat-row"><span>Invoices This Month</span><span>${sales.invoices_this_month}</span></div></div>`;
  html += `<div class="card"><h3>Purchases</h3>
    <div class="stat-row"><span>Total Ordered</span><span>${fmtMoney(purchases.total_ordered)} SAR</span></div>
    <div class="stat-row"><span>Orders This Month</span><span>${purchases.orders_this_month}</span></div></div>`;
  html += `<div class="card"><h3>Inventory Valuation</h3>
    <div class="stat-row"><span>Total Products</span><span>${inventory.total_products}</span></div>
    <div class="stat-row"><span>Total Stock Value</span><span>${fmtMoney(inventory.total_stock_value)} SAR</span></div>
    <div class="stat-row"><span>Low Stock Items</span><span>${inventory.low_stock_products}</span></div></div>`;
  html += `<div class="card"><h3>Trial Balance</h3>
    <div class="stat-row"><span>Total Debit</span><span>${fmtMoney(trial.total_debit)} SAR</span></div>
    <div class="stat-row"><span>Total Credit</span><span>${fmtMoney(trial.total_credit)} SAR</span></div></div>`;
  html += '</div>';
  html += `<div class="card"><h3>Export Reports</h3><div style="display:flex;gap:8px;flex-wrap:wrap;">
    ${['trial_balance','sales_by_customer','sales_by_product','stock_by_warehouse','inventory_by_category','purchase_by_supplier','payroll_runs','leads_by_source','leads_by_status','opportunities_by_stage']
      .map(key => `<div style="display:flex;gap:4px;">
        <button class="btn btn-sm btn-outline" onclick="downloadExport('/reports/export/${key}?format=csv','${key}.csv')">${key} (CSV)</button>
        <button class="btn btn-sm btn-outline" onclick="downloadExport('/reports/export/${key}?format=pdf','${key}.pdf')">PDF</button>
        <button class="btn btn-sm btn-outline" onclick="downloadExport('/reports/export/${key}?format=xlsx','${key}.xlsx')">XLSX</button>
      </div>`).join('')}
  </div></div>`;
  document.getElementById('content').innerHTML = html;
}

// ==================== AI Assistant ====================
let currentConversationId = null;
async function renderAiAssistant() {
  const status = await api('/ai/status');
  const statusLine = status.configured
    ? `<div style="font-size:12px;color:var(--text-muted);padding:4px 0;">Powered by ${status.provider} (${status.model})</div>`
    : `<div style="font-size:12px;color:var(--text-muted);padding:4px 0;">Running on real-data keyword answers — no LLM provider configured</div>`;
  document.getElementById('content').innerHTML = `<div class="card chat-box">
    ${statusLine}
    <div class="chat-messages" id="chat-messages"><div class="chat-msg assistant">Ask me about leads, customers, opportunities, sales, inventory, purchases, payroll, or cash/accounts — I answer from your real data.</div></div>
    <div class="chat-input"><input id="chat-input" placeholder="e.g. What's our cash position?" onkeydown="if(event.key==='Enter')sendAiMessage()"><button class="btn btn-primary" onclick="sendAiMessage()">Send</button></div>
  </div>`;
}
async function sendAiMessage() {
  const input = document.getElementById('chat-input');
  const msg = input.value.trim();
  if (!msg) return;
  const box = document.getElementById('chat-messages');
  box.innerHTML += `<div class="chat-msg user">${msg}</div>`;
  input.value = '';
  box.scrollTop = box.scrollHeight;
  try {
    const conv = await api('/ai/ask', { method: 'POST', body: JSON.stringify({ message: msg, conversation_id: currentConversationId }) });
    currentConversationId = conv.id;
    const reply = conv.messages[conv.messages.length - 1];
    box.innerHTML += `<div class="chat-msg assistant">${reply.content}</div>`;
    box.scrollTop = box.scrollHeight;
  } catch (err) { toast(err.message, true); }
}

// ==================== AI Insights ====================
async function renderAiInsights() {
  document.getElementById('content').innerHTML = `<div class="card"><h3>AI Insights</h3>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
      <button class="btn btn-outline btn-sm" onclick="loadAiInsight('dashboard')">Dashboard</button>
      <button class="btn btn-outline btn-sm" onclick="loadAiInsight('sales')">Sales</button>
      <button class="btn btn-outline btn-sm" onclick="loadAiInsight('inventory')">Inventory</button>
      <button class="btn btn-outline btn-sm" onclick="loadAiInsight('financial')">Financial</button>
      <button class="btn btn-outline btn-sm" onclick="loadAiInsight('crm')">CRM</button>
    </div>
    <div id="ai-insight-result" style="margin-top:16px;"></div></div>`;
}

async function loadAiInsight(type) {
  const el = document.getElementById('ai-insight-result');
  el.innerHTML = '<p>Generating…</p>';
  try {
    const result = await api(`/ai/insights/${type}`);
    const providerLine = result.provider ? `<div style="font-size:12px;color:var(--text-muted);">via ${result.provider} (${result.model})</div>` : `<div style="font-size:12px;color:var(--text-muted);">deterministic summary — no LLM provider configured</div>`;
    const suggestionLine = result.suggestion_raised ? '<div style="margin-top:8px;color:#F59E0B;">⚠ A new automation suggestion was raised — see AI Suggestions.</div>' : '';
    el.innerHTML = `<div class="card">${providerLine}<p>${result.summary}</p>${suggestionLine}</div>`;
  } catch (err) { el.innerHTML = `<p class="error-text">${err.message}</p>`; }
}

// ==================== AI Suggestions ====================
async function renderAiSuggestions() {
  const suggestions = await api('/ai/suggestions?page_size=30');
  let html = '<div class="card"><h3>AI Automation Suggestions</h3><table><thead><tr><th>Category</th><th>Title</th><th>Description</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
  (suggestions.data || suggestions).forEach(s => {
    let actions = '';
    if (s.status === 'open') {
      actions = `<button class="btn btn-sm btn-primary" onclick="actionSuggestion('${s.id}','mark-actioned')">Mark Actioned</button>
        <button class="btn btn-sm btn-outline" onclick="actionSuggestion('${s.id}','dismiss')">Dismiss</button>`;
    }
    html += `<tr><td>${s.category}</td><td>${s.title}</td><td>${s.description}</td><td>${badge(s.status)}</td><td>${actions}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function actionSuggestion(id, action) {
  try { await api(`/ai/suggestions/${id}/${action}`, { method: 'POST' }); renderAiSuggestions(); }
  catch (err) { alert(err.message); }
}

// ==================== AI Settings ====================
async function renderAiSettings() {
  const s = await api('/ai/settings');
  document.getElementById('content').innerHTML = `<div class="card"><h3>AI Settings</h3><form id="ai-settings-form">
    <div class="field"><label><input type="checkbox" name="is_enabled" ${s.is_enabled ? 'checked' : ''}> AI features enabled</label></div>
    <div class="field"><label><input type="checkbox" name="insights_enabled" ${s.insights_enabled ? 'checked' : ''}> AI Insights enabled</label></div>
    <div class="field"><label><input type="checkbox" name="notifications_enabled" ${s.notifications_enabled ? 'checked' : ''}> AI Notifications enabled</label></div>
    <div class="field"><label><input type="checkbox" name="automation_suggestions_enabled" ${s.automation_suggestions_enabled ? 'checked' : ''}> Automation Suggestions enabled</label></div>
    <div class="field"><label>Provider Override</label><select name="provider_override">
      <option value="" ${!s.provider_override ? 'selected' : ''}>Platform default</option>
      <option value="anthropic" ${s.provider_override==='anthropic'?'selected':''}>Anthropic</option>
      <option value="openai" ${s.provider_override==='openai'?'selected':''}>OpenAI</option>
    </select></div>
    <button class="btn btn-primary" type="submit">Save</button>
    <div class="error-text" id="modal-error"></div></form></div>`;

  document.getElementById('ai-settings-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(e.target);
    const payload = {
      is_enabled: f.has('is_enabled'), insights_enabled: f.has('insights_enabled'),
      notifications_enabled: f.has('notifications_enabled'), automation_suggestions_enabled: f.has('automation_suggestions_enabled'),
      provider_override: f.get('provider_override') || null,
    };
    try { await api('/ai/settings', { method: 'PATCH', body: JSON.stringify(payload) }); toast('AI settings saved'); }
    catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== AI Prompt Templates ====================
async function renderAiPrompts() {
  const prompts = await api('/ai/prompt-templates');
  let html = '<div class="card"><h3>AI Prompt Templates</h3>';
  prompts.forEach(p => {
    html += `<div class="field"><label>${p.key} ${p.is_custom ? badge('custom') : badge('default')}</label>
      <textarea id="prompt-${p.key}" rows="3" style="width:100%;">${p.content}</textarea>
      <div style="margin-top:4px;display:flex;gap:8px;">
        <button class="btn btn-sm btn-primary" onclick="savePrompt('${p.key}')">Save</button>
        <button class="btn btn-sm btn-outline" onclick="resetPrompt('${p.key}')">Reset to Default</button>
      </div></div>`;
  });
  html += '</div>';
  document.getElementById('content').innerHTML = html;
}

async function savePrompt(key) {
  const content = document.getElementById(`prompt-${key}`).value;
  try { await api('/ai/prompt-templates', { method: 'PUT', body: JSON.stringify({ key, content }) }); toast('Prompt saved'); renderAiPrompts(); }
  catch (err) { alert(err.message); }
}
async function resetPrompt(key) {
  try { await api(`/ai/prompt-templates/${key}/reset`, { method: 'POST' }); toast('Prompt reset to default'); renderAiPrompts(); }
  catch (err) { alert(err.message); }
}

// ==================== AI Activity Log ====================
async function renderAiActivityLog() {
  const logs = await api('/ai/activity-logs?page_size=50');
  let html = '<div class="card"><h3>AI Activity Log</h3><table><thead><tr><th>Feature</th><th>User</th><th>Provider</th><th>Summary</th><th>When</th></tr></thead><tbody>';
  (logs.data || logs).forEach(l => {
    html += `<tr><td>${l.feature}</td><td>${l.user_name || '—'}</td><td>${l.provider || 'deterministic'}</td><td>${(l.summary||'').slice(0,80)}</td><td>${fmtDate(l.created_at)}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Users / Roles / Company Settings ====================
async function renderUsers() {
  const users = await api('/admin/users?page_size=50');
  let html = '<div class="card"><table><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>MFA</th><th>Last Login</th></tr></thead><tbody>';
  (users.data || users).forEach(u => {
    html += `<tr><td>${u.full_name}</td><td>${u.email}</td><td>${u.role?u.role.name_en:'—'}</td><td>${badge(u.status)}</td><td>${u.mfa_enabled?'On':'Off'}</td><td>${u.last_login_at?fmtDate(u.last_login_at):'Never'}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function renderRoles() {
  const roles = await api('/admin/roles?page_size=50');
  let html = '<div class="card"><table><thead><tr><th>Role</th><th>System Role</th><th>Permissions</th></tr></thead><tbody>';
  (roles.data || roles).forEach(r => {
    html += `<tr><td>${r.name_en}</td><td>${r.is_system_role?'Yes':'No'}</td><td>${(r.permissions||[]).length} granted</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function renderCompanySettings() {
  const profile = await api('/admin/company-profile');
  document.getElementById('content').innerHTML = `<div class="card" style="max-width:520px;">
    <form id="cs-form">
      <div class="field"><label>Legal Name</label><input name="legal_name" value="${profile.legal_name||''}"></div>
      <div class="field"><label>Trade Name</label><input name="trade_name" value="${profile.trade_name||''}"></div>
      <div class="field"><label>VAT Number</label><input name="vat_number" value="${profile.vat_number||''}"></div>
      <div class="field"><label>Email</label><input name="email" type="email" value="${profile.email||''}"></div>
      <div class="field"><label>Phone</label><input name="phone" value="${profile.phone||''}"></div>
      <button class="btn btn-primary" type="submit">Save Changes</button>
      <div class="error-text" id="cs-error"></div>
    </form></div>`;
  document.getElementById('cs-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {}; new FormData(e.target).forEach((v,k) => payload[k]=v);
    try { await api('/admin/company-profile', { method: 'PATCH', body: JSON.stringify(payload) }); toast('Company profile updated.'); }
    catch (err) { document.getElementById('cs-error').textContent = err.message; }
  });
}

// ==================== Stock Transfers ====================
async function renderStockTransfers() {
  const [data, warehouses, products] = await Promise.all([
    api('/inventory/transfers?page_size=50'), api('/inventory/warehouses?page_size=100'), api('/inventory/products?page_size=100'),
  ]);
  const rows = data.data || data;
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openStockTransferModal()">+ New Transfer</button></div>
    <table><thead><tr><th>#</th><th>From</th><th>To</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead><tbody>`;
  if (!rows.length) html += '<tr><td colspan="6"><div class="empty-state">No stock transfers yet.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/inventory/transfers/"+r.id+"/complete","Transfer completed — stock moved."))-1}]()'>Complete</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.from_warehouse?r.from_warehouse.name:'—'}</td><td>${r.to_warehouse?r.to_warehouse.name:'—'}</td><td>${badge(r.status)}</td><td>${fmtDate(r.document_date)}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
  window.__whOpts = warehouses.map(w => `<option value="${w.id}">${w.name}</option>`).join('');
  window.__productOpts = products.map(p => `<option value="${p.id}">${p.sku} — ${p.name_en}</option>`).join('');
}

function openStockTransferModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Stock Transfer</h3><form id="st-form">
    <div class="field"><label>From Warehouse</label><select name="from_warehouse_id">${window.__whOpts}</select></div>
    <div class="field"><label>To Warehouse</label><select name="to_warehouse_id">${window.__whOpts}</select></div>
    <div class="field"><label>Items</label><div id="st-items"></div>
      <button type="button" class="btn btn-sm btn-outline" onclick="addSimpleItemRow('st-items')">+ Add Line</button></div>
    <div style="display:flex;gap:8px;margin-top:10px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  addSimpleItemRow('st-items');

  document.getElementById('st-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const items = collectSimpleItems('st-items');
    if (!items.length) { document.getElementById('modal-error').textContent = 'Add at least one line item.'; return; }
    const payload = { from_warehouse_id: e.target.from_warehouse_id.value, to_warehouse_id: e.target.to_warehouse_id.value, items };
    try { await api('/inventory/transfers', { method: 'POST', body: JSON.stringify(payload) }); closeModal(); toast('Transfer created.'); route(); }
    catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// Shared simple product+quantity line-item builder, used by Stock Transfers, Adjustments, Goods Receipts, and Goods Issues.
function addSimpleItemRow(containerId, extraField) {
  const row = document.createElement('div');
  row.className = 'item-row';
  row.style.gridTemplateColumns = extraField ? '2fr 1fr 1fr auto' : '2fr 1fr auto';
  let extra = '';
  if (extraField === 'cost') extra = `<input class="item-extra" type="number" placeholder="Unit Cost" step="0.01">`;
  if (extraField === 'signed') extra = `<input class="item-extra" type="number" placeholder="+/- Qty Change" step="0.001">`;
  row.innerHTML = `<select class="item-product">${window.__productOpts}</select>
    ${extraField === 'signed' ? '' : '<input class="item-qty" type="number" placeholder="Qty" value="1" min="0.001" step="0.001">'}
    ${extra}
    <button type="button" class="btn btn-sm btn-danger" onclick="this.closest('.item-row').remove()">×</button>`;
  document.getElementById(containerId).appendChild(row);
}

function collectSimpleItems(containerId, extraField) {
  return [...document.querySelectorAll(`#${containerId} .item-row`)].map(row => {
    const item = { product_id: row.querySelector('.item-product').value };
    if (extraField === 'signed') {
      item.quantity_change = parseFloat(row.querySelector('.item-extra').value) || 0;
    } else {
      item.quantity = parseFloat(row.querySelector('.item-qty').value) || 0;
      if (extraField === 'cost') item.unit_cost = parseFloat(row.querySelector('.item-extra').value) || 0;
    }
    return item;
  }).filter(i => i.product_id && (i.quantity > 0 || i.quantity_change));
}

// ==================== Stock Adjustments ====================
async function renderStockAdjustments() {
  const [data, warehouses, products] = await Promise.all([
    api('/inventory/adjustments?page_size=50'), api('/inventory/warehouses?page_size=100'), api('/inventory/products?page_size=100'),
  ]);
  const rows = data.data || data;
  window.__whOpts = warehouses.map(w => `<option value="${w.id}">${w.name}</option>`).join('');
  window.__productOpts = products.map(p => `<option value="${p.id}">${p.sku} — ${p.name_en}</option>`).join('');
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openAdjustmentModal()">+ New Adjustment</button></div>
    <table><thead><tr><th>#</th><th>Warehouse</th><th>Status</th><th>Reason</th><th>Actions</th></tr></thead><tbody>`;
  if (!rows.length) html += '<tr><td colspan="5"><div class="empty-state">No stock adjustments yet.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/inventory/adjustments/"+r.id+"/approve","Adjustment approved."))-1}]()'>Approve</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.warehouse?r.warehouse.name:'—'}</td><td>${badge(r.status)}</td><td>${r.reason||'—'}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

function openAdjustmentModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Stock Adjustment</h3><form id="adj-form">
    <div class="field"><label>Warehouse</label><select name="warehouse_id">${window.__whOpts}</select></div>
    <div class="field"><label>Reason</label><input name="reason" placeholder="e.g. Monthly stock count"></div>
    <div class="field"><label>Items (use negative quantity for shrinkage/write-off)</label><div id="adj-items"></div>
      <button type="button" class="btn btn-sm btn-outline" onclick="addSimpleItemRow('adj-items','signed')">+ Add Line</button></div>
    <div style="display:flex;gap:8px;margin-top:10px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  addSimpleItemRow('adj-items', 'signed');

  document.getElementById('adj-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const items = collectSimpleItems('adj-items', 'signed');
    if (!items.length) { document.getElementById('modal-error').textContent = 'Add at least one line item with a non-zero change.'; return; }
    const payload = { warehouse_id: e.target.warehouse_id.value, reason: e.target.reason.value, items };
    try { await api('/inventory/adjustments', { method: 'POST', body: JSON.stringify(payload) }); closeModal(); toast('Adjustment created — approve it to apply.'); route(); }
    catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== Goods Receipts ====================
async function renderGoodsReceipts() {
  const [data, warehouses, products] = await Promise.all([
    api('/inventory/goods-receipts?page_size=50'), api('/inventory/warehouses?page_size=100'), api('/inventory/products?page_size=100'),
  ]);
  const rows = data.data || data;
  window.__whOpts = warehouses.map(w => `<option value="${w.id}">${w.name}</option>`).join('');
  window.__productOpts = products.map(p => `<option value="${p.id}">${p.sku} — ${p.name_en}</option>`).join('');
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openGoodsReceiptModal()">+ New Goods Receipt</button></div>
    <table><thead><tr><th>#</th><th>Warehouse</th><th>Supplier</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
  if (!rows.length) html += '<tr><td colspan="5"><div class="empty-state">No goods receipts yet — receive a Purchase Order or record one directly.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/inventory/goods-receipts/"+r.id+"/receive","Goods received — stock updated."))-1}]()'>Receive</button>`
      : (r.supplier ? `<button class="btn btn-sm btn-outline" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/purchase/goods-receipts/"+r.id+"/bill","Supplier bill created — go to Supplier Bills to approve it."))-1}]()'>Bill</button>` : '');
    html += `<tr><td>${r.document_number}</td><td>${r.warehouse?r.warehouse.name:'—'}</td><td>${r.supplier?r.supplier.name:'—'}</td><td>${badge(r.status)}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

function openGoodsReceiptModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Goods Receipt</h3><form id="grn-form">
    <div class="field"><label>Warehouse</label><select name="warehouse_id">${window.__whOpts}</select></div>
    <div class="field"><label>Items</label><div id="grn-items"></div>
      <button type="button" class="btn btn-sm btn-outline" onclick="addSimpleItemRow('grn-items','cost')">+ Add Line</button></div>
    <div style="display:flex;gap:8px;margin-top:10px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  addSimpleItemRow('grn-items', 'cost');

  document.getElementById('grn-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const items = collectSimpleItems('grn-items', 'cost');
    if (!items.length) { document.getElementById('modal-error').textContent = 'Add at least one line item.'; return; }
    const payload = { warehouse_id: e.target.warehouse_id.value, items };
    try { await api('/inventory/goods-receipts', { method: 'POST', body: JSON.stringify(payload) }); closeModal(); toast('Goods receipt created.'); route(); }
    catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== Goods Issues ====================
async function renderGoodsIssues() {
  const [data, warehouses, products] = await Promise.all([
    api('/inventory/goods-issues?page_size=50'), api('/inventory/warehouses?page_size=100'), api('/inventory/products?page_size=100'),
  ]);
  const rows = data.data || data;
  window.__whOpts = warehouses.map(w => `<option value="${w.id}">${w.name}</option>`).join('');
  window.__productOpts = products.map(p => `<option value="${p.id}">${p.sku} — ${p.name_en}</option>`).join('');
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openGoodsIssueModal()">+ New Goods Issue</button></div>
    <table><thead><tr><th>#</th><th>Warehouse</th><th>Issued To</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
  if (!rows.length) html += '<tr><td colspan="5"><div class="empty-state">No goods issues yet.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/inventory/goods-issues/"+r.id+"/issue","Goods issued — stock and accounting updated."))-1}]()'>Issue</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.warehouse?r.warehouse.name:'—'}</td><td>${r.issued_to||'—'}</td><td>${badge(r.status)}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

function openGoodsIssueModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Goods Issue</h3><form id="gi-form">
    <div class="field"><label>Warehouse</label><select name="warehouse_id">${window.__whOpts}</select></div>
    <div class="field"><label>Issued To</label><input name="issued_to" placeholder="e.g. Marketing Dept"></div>
    <div class="field"><label>Reason</label><input name="reason"></div>
    <div class="field"><label>Items</label><div id="gi-items"></div>
      <button type="button" class="btn btn-sm btn-outline" onclick="addSimpleItemRow('gi-items')">+ Add Line</button></div>
    <div style="display:flex;gap:8px;margin-top:10px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  addSimpleItemRow('gi-items');

  document.getElementById('gi-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const items = collectSimpleItems('gi-items');
    if (!items.length) { document.getElementById('modal-error').textContent = 'Add at least one line item.'; return; }
    const payload = { warehouse_id: e.target.warehouse_id.value, issued_to: e.target.issued_to.value, reason: e.target.reason.value, items };
    try { await api('/inventory/goods-issues', { method: 'POST', body: JSON.stringify(payload) }); closeModal(); toast('Goods issue created.'); route(); }
    catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== Purchase Dashboard ====================
async function renderPurchaseDashboard() {
  const d = await api('/purchase/dashboard');
  const metric = (label, value) => `<div class="card"><div class="metric-value">${value}</div><div class="metric-label">${label}</div></div>`;
  let html = '<div class="grid grid-4">';
  html += metric('Purchase Orders', d.document_counts.purchase_orders);
  html += metric('Goods Receipts', d.document_counts.goods_receipts);
  html += metric('Supplier Bills', d.document_counts.supplier_bills);
  html += metric('Debit Notes', d.document_counts.debit_notes);
  html += metric('Spend This Month', fmtMoney(d.spend_this_month) + ' SAR');
  html += metric('Payments This Month', fmtMoney(d.payments_this_month) + ' SAR');
  html += metric('Outstanding Payables', fmtMoney(d.outstanding_payables) + ' SAR');
  html += metric('Overdue Bills', d.overdue_bills);
  html += '</div>';

  const aging = await api('/reports/aging-payables');
  html += `<div class="card"><h3>Payables aging</h3><div class="grid grid-4">
    <div><div class="metric-value">${fmtMoney(aging.current)}</div><div class="metric-label">Current</div></div>
    <div><div class="metric-value">${fmtMoney(aging.days_1_30)}</div><div class="metric-label">1-30 Days</div></div>
    <div><div class="metric-value">${fmtMoney(aging.days_31_60)}</div><div class="metric-label">31-60 Days</div></div>
    <div><div class="metric-value">${fmtMoney(aging.days_90_plus)}</div><div class="metric-label">90+ Days</div></div>
  </div></div>`;
  document.getElementById('content').innerHTML = html;
}

// ==================== Supplier Bills ====================
async function renderSupplierBills() {
  const [data, suppliers, products] = await Promise.all([
    api('/purchase/bills?page_size=50'), api('/purchase/suppliers?page_size=100'), api('/inventory/products?page_size=100'),
  ]);
  const rows = data.data || data;
  window.__supplierOpts = suppliers.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
  window.__productOpts = products.map(p => `<option value="${p.id}">${p.sku} — ${p.name_en}</option>`).join('');
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openSupplierBillModal()">+ New Bill</button></div>
    <table><thead><tr><th>#</th><th>Supplier</th><th>Status</th><th>Total</th><th>Balance due</th><th>Actions</th></tr></thead><tbody>`;
  if (!rows.length) html += '<tr><td colspan="6"><div class="empty-state">No supplier bills yet.</div></td></tr>';
  rows.forEach(r => {
    const actions = [];
    if (r.status === 'draft') actions.push(`<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/purchase/bills/"+r.id+"/approve","Bill approved — posted to Accounts Payable."))-1}]()'>Approve</button>`);
    if (['approved', 'partial'].includes(r.status)) actions.push(`<button class="btn btn-sm btn-outline" onclick='docActionRegistry[${window.__docActions.push(() => recordSupplierPayment(r))-1}]()'>Record payment</button>`);
    html += `<tr><td>${r.document_number}</td><td>${r.supplier?r.supplier.name:'—'}</td><td>${badge(r.status)}</td><td>${fmtMoney(r.total)}</td><td>${fmtMoney(r.balance_due)}</td><td>${actions.join(' ')}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function recordSupplierPayment(bill) {
  const amount = prompt(`Record payment for ${bill.document_number} (balance due: ${fmtMoney(bill.balance_due)} SAR):`);
  if (!amount) return;
  try {
    await api(`/purchase/bills/${bill.id}/record-payment`, { method: 'POST', body: JSON.stringify({ amount: parseFloat(amount) }) });
    toast('Payment recorded.');
    route();
  } catch (err) { toast(err.message, true); }
}

function openSupplierBillModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Supplier Bill</h3><form id="bill-form">
    <div class="field"><label>Supplier</label><select name="supplier_id">${window.__supplierOpts}</select></div>
    <div class="field"><label>Line Items</label><div id="bill-items"></div>
      <button type="button" class="btn btn-sm btn-outline" onclick="addSimpleItemRow('bill-items','cost')">+ Add Line</button></div>
    <div style="display:flex;gap:8px;margin-top:10px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  addSimpleItemRow('bill-items', 'cost');

  document.getElementById('bill-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const items = collectSimpleItems('bill-items', 'cost').map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_cost: i.unit_cost }));
    if (!items.length) { document.getElementById('modal-error').textContent = 'Add at least one line item.'; return; }
    try {
      await api('/purchase/bills', { method: 'POST', body: JSON.stringify({ supplier_id: e.target.supplier_id.value, items }) });
      closeModal(); toast('Supplier bill created.'); route();
    } catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== Supplier Payments ====================
async function renderSupplierPayments() {
  const data = await api('/purchase/payments?page_size=50');
  const rows = data.data || data;
  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openSupplierPaymentModal()">+ Record Payment</button></div>
    <table><thead><tr><th>#</th><th>Supplier</th><th>Amount</th><th>Allocated</th><th>Unallocated</th><th>Method</th><th>Date</th></tr></thead><tbody>`;
  if (!rows.length) html += '<tr><td colspan="7"><div class="empty-state">No supplier payments recorded yet.</div></td></tr>';
  rows.forEach(r => {
    html += `<tr><td>${r.payment_number}</td><td>${r.supplier?r.supplier.name:'—'}</td><td>${fmtMoney(r.amount)}</td><td>${fmtMoney(r.allocated_amount)}</td><td>${fmtMoney(r.unallocated_amount)}</td><td>${r.payment_method}</td><td>${fmtDate(r.payment_date)}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function openSupplierPaymentModal() {
  const suppliers = await api('/purchase/suppliers?page_size=100');
  const opts = suppliers.map(s => `<option value="${s.id}">${s.name}</option>`).join('');
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>Record Supplier Payment</h3><form id="spay-form">
    <div class="field"><label>Supplier</label><select name="supplier_id">${opts}</select></div>
    <div class="field"><label>Amount (SAR)</label><input name="amount" type="number" step="0.01" required></div>
    <div class="field"><label>Method</label><select name="payment_method"><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="card">Card</option><option value="other">Other</option></select></div>
    <div class="field"><label>Reference</label><input name="reference"></div>
    <p style="font-size:12px;color:var(--text-muted);">This records an unallocated payment. Use "Record payment" on a specific bill to pay it directly, or allocate this payment afterward via the API.</p>
    <div style="display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Record</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);
  document.getElementById('spay-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const payload = {}; new FormData(e.target).forEach((v,k) => payload[k]=v);
    try { await api('/purchase/payments', { method: 'POST', body: JSON.stringify(payload) }); closeModal(); toast('Payment recorded.'); route(); }
    catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== Debit Notes ====================
async function renderDebitNotes() {
  const data = await api('/purchase/debit-notes?page_size=50');
  const rows = data.data || data;
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = '<div class="card"><table><thead><tr><th>#</th><th>Supplier</th><th>Status</th><th>Total</th><th>Reason</th><th>Actions</th></tr></thead><tbody>';
  if (!rows.length) html += '<tr><td colspan="6"><div class="empty-state">No debit notes yet — issue one against a bill, or receive a Purchase Return to auto-generate one.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/purchase/debit-notes/"+r.id+"/issue","Debit note issued."))-1}]()'>Issue</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.supplier?r.supplier.name:'—'}</td><td>${badge(r.status)}</td><td>${fmtMoney(r.total)}</td><td>${r.reason||'—'}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Purchase Returns ====================
async function renderPurchaseReturns() {
  const data = await api('/purchase/returns?page_size=50');
  const rows = data.data || data;
  window.__docActions = window.__docActions || []; window.__docActions.length = 0;

  let html = '<div class="card"><table><thead><tr><th>#</th><th>Supplier</th><th>Status</th><th>Reason</th><th>Debit note</th><th>Actions</th></tr></thead><tbody>';
  if (!rows.length) html += '<tr><td colspan="6"><div class="empty-state">No purchase returns yet.</div></td></tr>';
  rows.forEach(r => {
    const action = r.status === 'draft'
      ? `<button class="btn btn-sm btn-success" onclick='docActionRegistry[${window.__docActions.push(() => simpleAction("/purchase/returns/"+r.id+"/return","Goods returned — stock and debit note updated."))-1}]()'>Return</button>`
      : '';
    html += `<tr><td>${r.document_number}</td><td>${r.supplier?r.supplier.name:'—'}</td><td>${badge(r.status)}</td><td>${r.reason||'—'}</td><td>${r.debit_note_id?'Yes':'—'}</td><td>${action}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Income Statement ====================
async function renderIncomeStatement() {
  const d = await api('/reports/income-statement');
  let html = '<div class="card"><h3>Income Statement (Profit &amp; Loss)</h3>';
  html += '<table><thead><tr><th>Account</th><th>Amount</th></tr></thead><tbody>';
  html += '<tr><td colspan="2" style="font-weight:600;">Revenue</td></tr>';
  (d.revenue||[]).forEach(r => { html += `<tr><td>${r.code} — ${r.name_en}</td><td>${fmtMoney(r.balance)}</td></tr>`; });
  html += `<tr style="font-weight:600;"><td>Total Revenue</td><td>${fmtMoney(d.total_revenue)}</td></tr>`;
  html += '<tr><td colspan="2" style="font-weight:600;padding-top:12px;">Expenses</td></tr>';
  (d.expenses||[]).forEach(r => { html += `<tr><td>${r.code} — ${r.name_en}</td><td>${fmtMoney(r.balance)}</td></tr>`; });
  html += `<tr style="font-weight:600;"><td>Total Expenses</td><td>${fmtMoney(d.total_expenses)}</td></tr>`;
  html += `<tr style="font-weight:700;border-top:2px solid var(--border);"><td>Net Income</td><td>${fmtMoney(d.net_income)}</td></tr>`;
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Balance Sheet ====================
async function renderBalanceSheet() {
  const d = await api('/reports/balance-sheet');
  const section = (title, rows, total) => {
    let s = `<tr><td colspan="2" style="font-weight:600;padding-top:12px;">${title}</td></tr>`;
    (rows||[]).forEach(r => { s += `<tr><td>${r.code} — ${r.name_en}</td><td>${fmtMoney(r.balance)}</td></tr>`; });
    s += `<tr style="font-weight:600;"><td>Total ${title}</td><td>${fmtMoney(total)}</td></tr>`;
    return s;
  };
  let html = `<div class="card"><h3>Balance Sheet ${d.balanced ? badge('balanced') : badge('out of balance')}</h3>`;
  html += '<table><thead><tr><th>Account</th><th>Amount</th></tr></thead><tbody>';
  html += section('Assets', d.assets, d.total_assets);
  html += section('Liabilities', d.liabilities, d.total_liabilities);
  html += '<tr><td colspan="2" style="font-weight:600;padding-top:12px;">Equity</td></tr>';
  (d.equity||[]).forEach(r => { html += `<tr><td>${r.code} — ${r.name_en}</td><td>${fmtMoney(r.balance)}</td></tr>`; });
  html += `<tr><td>Retained Earnings</td><td>${fmtMoney(d.retained_earnings)}</td></tr>`;
  html += `<tr style="font-weight:600;"><td>Total Equity</td><td>${fmtMoney(d.total_equity)}</td></tr>`;
  html += `<tr style="font-weight:700;border-top:2px solid var(--border);"><td>Total Liabilities &amp; Equity</td><td>${fmtMoney(d.total_liabilities_and_equity)}</td></tr>`;
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== HR Dashboard ====================
async function renderHrDashboard() {
  const d = await api('/hr/dashboard');
  const metric = (label, value) => `<div class="card"><div class="metric-value">${value}</div><div class="metric-label">${label}</div></div>`;
  let html = '<div class="grid grid-4">';
  html += metric('Active Employees', d.employee_counts.active);
  html += metric('On Leave', d.employee_counts.on_leave);
  html += metric('Present Today', d.attendance_today.present);
  html += metric('Absent Today', d.attendance_today.absent);
  html += metric('Pending Leave Requests', d.pending_leave_requests);
  html += metric('Latest Payroll Run', d.latest_payroll_run ? d.latest_payroll_run.run_number + ' (' + d.latest_payroll_run.status + ')' : '—');
  html += '</div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Employees ====================
async function renderEmployees() {
  const employees = await api('/hr/employees?page_size=50');
  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openEmployeeModal()">+ New Employee</button></div>
    <table><thead><tr><th>#</th><th>Name</th><th>Department</th><th>Designation</th><th>Hire Date</th><th>Basic Salary</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
  (employees.data || employees).forEach(e => {
    html += `<tr><td>${e.employee_number}</td><td>${e.full_name}</td><td>${e.department?.title_en || e.department?.name_en || '—'}</td>
      <td>${e.designation?.title_en || '—'}</td><td>${fmtDate(e.hire_date)}</td><td>${fmtMoney(e.basic_salary)}</td>
      <td>${badge(e.employment_status)}</td>
      <td>${e.employment_status !== 'terminated' ? `<button class="btn btn-sm btn-outline" onclick="terminateEmployee('${e.id}')">Terminate</button>` : ''}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

function openEmployeeModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Employee</h3><form id="emp-form">
    <div class="field"><label>Full Name</label><input name="full_name" required></div>
    <div class="field"><label>Email</label><input name="email" type="email"></div>
    <div class="field"><label>Phone</label><input name="phone"></div>
    <div class="field"><label>Hire Date</label><input name="hire_date" type="date" required></div>
    <div class="field"><label>Basic Salary</label><input name="basic_salary" type="number" step="0.01" required></div>
    <div style="margin-top:16px;display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);

  document.getElementById('emp-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(e.target);
    try {
      await api('/hr/employees', { method: 'POST', body: JSON.stringify(Object.fromEntries(f)) });
      closeModal(); renderEmployees();
    } catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

async function terminateEmployee(id) {
  const date = prompt('Termination date (YYYY-MM-DD):', new Date().toISOString().slice(0,10));
  if (!date) return;
  try {
    await api(`/hr/employees/${id}/terminate`, { method: 'POST', body: JSON.stringify({ termination_date: date }) });
    renderEmployees();
  } catch (err) { alert(err.message); }
}

// ==================== Attendance ====================
async function renderAttendance() {
  const records = await api('/hr/attendance?page_size=50');
  let html = '<div class="card"><h3>Attendance</h3><table><thead><tr><th>Employee</th><th>Date</th><th>Check In</th><th>Check Out</th><th>Hours</th><th>Status</th></tr></thead><tbody>';
  (records.data || records).forEach(a => {
    html += `<tr><td>${a.employee_name || '—'}</td><td>${fmtDate(a.date)}</td><td>${a.check_in ? new Date(a.check_in).toLocaleTimeString() : '—'}</td>
      <td>${a.check_out ? new Date(a.check_out).toLocaleTimeString() : '—'}</td><td>${a.hours_worked ?? '—'}</td><td>${badge(a.status)}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== Leave Requests ====================
async function renderLeaveRequests() {
  const requests = await api('/hr/leave-requests?page_size=50');
  let html = '<div class="card"><h3>Leave Requests</h3><table><thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
  (requests.data || requests).forEach(r => {
    let actions = '';
    if (r.status === 'pending') {
      actions = `<button class="btn btn-sm btn-primary" onclick="leaveAction('${r.id}','approve')">Approve</button>
        <button class="btn btn-sm btn-outline" onclick="leaveAction('${r.id}','reject')">Reject</button>`;
    }
    html += `<tr><td>${r.employee_name || '—'}</td><td>${r.leave_type?.name_en || '—'}</td><td>${fmtDate(r.start_date)} – ${fmtDate(r.end_date)}</td>
      <td>${r.days_count}</td><td>${badge(r.status)}</td><td>${actions}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function leaveAction(id, action) {
  try { await api(`/hr/leave-requests/${id}/${action}`, { method: 'POST' }); renderLeaveRequests(); }
  catch (err) { alert(err.message); }
}

// ==================== Payroll Runs ====================
async function renderPayrollRuns() {
  const runs = await api('/hr/payroll-runs?page_size=30');
  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openProcessPayrollModal()">+ Process Payroll</button></div>
    <table><thead><tr><th>Run</th><th>Period</th><th>Gross</th><th>Deductions</th><th>Net</th><th>Status</th><th>Actions</th></tr></thead><tbody>`;
  (runs.data || runs).forEach(r => {
    html += `<tr><td>${r.run_number}</td><td>${r.period_month}/${r.period_year}</td><td>${fmtMoney(r.total_gross)}</td>
      <td>${fmtMoney(r.total_deductions)}</td><td>${fmtMoney(r.total_net)}</td><td>${badge(r.status)}</td>
      <td>${r.status === 'processed' ? `<button class="btn btn-sm btn-outline" onclick="markPayrollPaid('${r.id}')">Mark Paid</button>` : ''}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

function openProcessPayrollModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  const now = new Date();
  overlay.innerHTML = `<div class="modal"><h3>Process Payroll Run</h3><form id="pr-form">
    <div class="field"><label>Month</label><input name="month" type="number" min="1" max="12" value="${now.getMonth()+1}" required></div>
    <div class="field"><label>Year</label><input name="year" type="number" value="${now.getFullYear()}" required></div>
    <div style="margin-top:16px;display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Process</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);

  document.getElementById('pr-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = Object.fromEntries(new FormData(e.target));
    try {
      await api('/hr/payroll-runs/process', { method: 'POST', body: JSON.stringify({ month: parseInt(f.month), year: parseInt(f.year) }) });
      closeModal(); renderPayrollRuns();
    } catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

async function markPayrollPaid(id) {
  try { await api(`/hr/payroll-runs/${id}/mark-paid`, { method: 'POST' }); renderPayrollRuns(); }
  catch (err) { alert(err.message); }
}

// ==================== Recruitment ====================
async function renderRecruitment() {
  const [openings, applications] = await Promise.all([api('/hr/job-openings?page_size=30'), api('/hr/job-applications?page_size=30')]);
  let html = '<div class="card"><h3>Job Openings</h3><table><thead><tr><th>Title</th><th>Positions</th><th>Applications</th><th>Status</th></tr></thead><tbody>';
  (openings.data || openings).forEach(o => {
    html += `<tr><td>${o.title}</td><td>${o.positions_count}</td><td>${o.applications_count ?? '—'}</td><td>${badge(o.status)}</td></tr>`;
  });
  html += '</tbody></table></div>';

  html += '<div class="card"><h3>Applications</h3><table><thead><tr><th>Candidate</th><th>Job Opening</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
  (applications.data || applications).forEach(a => {
    let actions = '';
    if (a.status !== 'hired' && a.status !== 'rejected') {
      actions = `<select onchange="updateApplicationStatus('${a.id}', this.value)">
        <option value="">Change status…</option>
        <option value="screening">Screening</option><option value="interview">Interview</option>
        <option value="offered">Offered</option><option value="rejected">Rejected</option>
      </select> <button class="btn btn-sm btn-primary" onclick="hireApplication('${a.id}')">Hire</button>`;
    }
    html += `<tr><td>${a.candidate?.full_name || '—'}</td><td>${a.job_opening?.title || '—'}</td><td>${badge(a.status)}</td><td>${actions}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function updateApplicationStatus(id, status) {
  if (!status) return;
  try { await api(`/hr/job-applications/${id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) }); renderRecruitment(); }
  catch (err) { alert(err.message); }
}

async function hireApplication(id) {
  const hireDate = prompt('Hire date (YYYY-MM-DD):', new Date().toISOString().slice(0,10));
  if (!hireDate) return;
  const salary = prompt('Basic salary:');
  if (!salary) return;
  try {
    await api(`/hr/job-applications/${id}/hire`, { method: 'POST', body: JSON.stringify({ hire_date: hireDate, basic_salary: parseFloat(salary) }) });
    renderRecruitment();
  } catch (err) { alert(err.message); }
}

// ==================== Performance Reviews ====================
async function renderPerformanceReviews() {
  const reviews = await api('/hr/performance-reviews?page_size=30');
  let html = '<div class="card"><h3>Performance Reviews</h3><table><thead><tr><th>Employee</th><th>Rating</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
  (reviews.data || reviews).forEach(r => {
    let actions = '';
    if (r.status === 'draft') actions = `<button class="btn btn-sm btn-primary" onclick="submitReview('${r.id}')">Submit</button>`;
    if (r.status === 'submitted') actions = `<button class="btn btn-sm btn-outline" onclick="acknowledgeReview('${r.id}')">Acknowledge</button>`;
    html += `<tr><td>${r.employee_name || '—'}</td><td>${r.rating ?? '—'} / 5</td><td>${badge(r.status)}</td><td>${actions}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

async function submitReview(id) {
  try { await api(`/hr/performance-reviews/${id}/submit`, { method: 'POST' }); renderPerformanceReviews(); }
  catch (err) { alert(err.message); }
}
async function acknowledgeReview(id) {
  try { await api(`/hr/performance-reviews/${id}/acknowledge`, { method: 'POST' }); renderPerformanceReviews(); }
  catch (err) { alert(err.message); }
}

// ==================== My Self-Service (ESS) ====================
async function renderMyEss() {
  let profile;
  try { profile = await api('/ess/profile'); }
  catch (err) {
    document.getElementById('content').innerHTML = `<div class="card"><p>${err.message}</p></div>`;
    return;
  }

  const [attendance, leaveRequests, payslips] = await Promise.all([
    api('/ess/attendance'), api('/ess/leave-requests'), api('/ess/payslips'),
  ]);

  let html = `<div class="card"><h3>${profile.full_name}</h3><p>${profile.designation?.title_en || ''} — ${profile.department?.name_en || ''}</p>
    <div style="display:flex;gap:8px;"><button class="btn btn-primary btn-sm" onclick="essCheckIn()">Check In</button>
    <button class="btn btn-outline btn-sm" onclick="essCheckOut()">Check Out</button>
    <button class="btn btn-outline btn-sm" onclick="openEssLeaveModal()">Request Leave</button></div></div>`;

  html += '<div class="card"><h3>My Recent Attendance</h3><table><thead><tr><th>Date</th><th>Check In</th><th>Check Out</th><th>Status</th></tr></thead><tbody>';
  attendance.slice(0, 10).forEach(a => {
    html += `<tr><td>${fmtDate(a.date)}</td><td>${a.check_in ? new Date(a.check_in).toLocaleTimeString() : '—'}</td><td>${a.check_out ? new Date(a.check_out).toLocaleTimeString() : '—'}</td><td>${badge(a.status)}</td></tr>`;
  });
  html += '</tbody></table></div>';

  html += '<div class="card"><h3>My Leave Requests</h3><table><thead><tr><th>Type</th><th>Dates</th><th>Status</th></tr></thead><tbody>';
  leaveRequests.forEach(r => {
    html += `<tr><td>${r.leave_type?.name_en || '—'}</td><td>${fmtDate(r.start_date)} – ${fmtDate(r.end_date)}</td><td>${badge(r.status)}</td></tr>`;
  });
  html += '</tbody></table></div>';

  html += '<div class="card"><h3>My Payslips</h3><table><thead><tr><th>Gross</th><th>Deductions</th><th>Net Pay</th><th>Status</th></tr></thead><tbody>';
  payslips.forEach(p => {
    html += `<tr><td>${fmtMoney(p.gross_pay)}</td><td>${fmtMoney(p.total_deductions)}</td><td>${fmtMoney(p.net_pay)}</td><td>${badge(p.status)}</td></tr>`;
  });
  html += '</tbody></table></div>';

  document.getElementById('content').innerHTML = html;
}

async function essCheckIn() { try { await api('/ess/attendance/check-in', { method: 'POST' }); renderMyEss(); } catch (err) { alert(err.message); } }
async function essCheckOut() { try { await api('/ess/attendance/check-out', { method: 'POST' }); renderMyEss(); } catch (err) { alert(err.message); } }

function openEssLeaveModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>Request Leave</h3><form id="ess-leave-form">
    <div class="field"><label>Leave Type ID</label><input name="leave_type_id" required placeholder="Ask HR for the leave type ID"></div>
    <div class="field"><label>Start Date</label><input name="start_date" type="date" required></div>
    <div class="field"><label>End Date</label><input name="end_date" type="date" required></div>
    <div class="field"><label>Reason</label><textarea name="reason"></textarea></div>
    <div style="margin-top:16px;display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Submit</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);

  document.getElementById('ess-leave-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = new FormData(e.target);
    try {
      await api('/ess/leave-requests', { method: 'POST', body: JSON.stringify(Object.fromEntries(f)) });
      closeModal(); renderMyEss();
    } catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

// ==================== Executive Dashboard ====================
async function renderExecutiveDashboard() {
  const d = await api('/reports/executive-summary');
  const metric = (label, value) => `<div class="card"><div class="metric-value">${value}</div><div class="metric-label">${label}</div></div>`;
  let html = '<div class="grid grid-4">';
  html += metric('Cash Position', fmtMoney(d.cash_position) + ' SAR');
  html += metric('Accounts Receivable', fmtMoney(d.accounts_receivable) + ' SAR');
  html += metric('Accounts Payable', fmtMoney(d.accounts_payable) + ' SAR');
  html += metric('Sales This Month', fmtMoney(d.sales_this_month) + ' SAR');
  html += metric('Purchases This Month', fmtMoney(d.purchases_this_month) + ' SAR');
  html += metric('Open Purchase Orders', d.open_purchase_orders);
  html += metric('Active Employees', d.active_employees);
  html += metric('Open Leads', d.open_leads);
  html += metric('Open Opportunity Value', fmtMoney(d.open_opportunity_value) + ' SAR');
  html += metric('Low Stock Products', d.low_stock_products);
  html += '</div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== KPI Dashboard ====================
async function renderKpiDashboard() {
  const d = await api('/reports/kpi-summary');
  const trendCard = (label, t) => {
    const arrow = t.change_percent === null ? '' : (t.change_percent >= 0 ? '▲' : '▼');
    const color = t.change_percent === null ? 'var(--text-muted)' : (t.change_percent >= 0 ? '#10B981' : '#EF4444');
    return `<div class="card"><div class="metric-value">${fmtMoney(t.current)}</div><div class="metric-label">${label}</div>
      <div style="color:${color};font-size:13px;margin-top:4px;">${arrow} ${t.change_percent === null ? 'n/a (no prior data)' : t.change_percent + '% vs last month'}</div></div>`;
  };
  let html = '<div class="grid grid-3">';
  html += trendCard('Revenue', d.revenue);
  html += trendCard('Purchase Spend', d.purchase_spend);
  html += trendCard('New Leads', d.new_leads);
  html += `<div class="card"><div class="metric-value">${d.headcount}</div><div class="metric-label">Active Headcount</div></div>`;
  html += '</div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== CRM Reports ====================
async function renderCrmReports() {
  const [bySource, byStatus, byStage, funnel] = await Promise.all([
    api('/reports/leads-by-source'), api('/reports/leads-by-status'),
    api('/reports/opportunities-by-stage'), api('/reports/conversion-funnel'),
  ]);
  let html = '<div class="card"><h3>Leads by Source</h3>' + svgBarChart(bySource, 'source', 'total') + '</div>';
  html += '<div class="card"><h3>Leads by Status</h3>' + svgBarChart(byStatus, 'status', 'total') + '</div>';
  html += '<div class="card"><h3>Opportunities by Stage</h3>' + svgBarChart(byStage, 'stage', 'total_amount') + '</div>';
  html += `<div class="card"><h3>Conversion Funnel</h3>
    <div class="stat-row"><span>Total Leads</span><span>${funnel.total_leads}</span></div>
    <div class="stat-row"><span>Won Leads</span><span>${funnel.won_leads}</span></div>
    <div class="stat-row"><span>Converted to Customer</span><span>${funnel.converted_to_customer}</span></div>
    <div class="stat-row"><span>Lead → Customer Rate</span><span>${funnel.lead_to_customer_rate}%</span></div>
    <div class="stat-row"><span>Total Opportunities</span><span>${funnel.total_opportunities}</span></div>
    <div class="stat-row"><span>Won Opportunities</span><span>${funnel.won_opportunities}</span></div>
    <div class="stat-row"><span>Opportunity Win Rate</span><span>${funnel.opportunity_win_rate}%</span></div></div>`;
  document.getElementById('content').innerHTML = html;
}

// ==================== Cash Flow ====================
async function renderCashFlow() {
  const d = await api('/reports/cash-flow');
  let html = `<div class="card"><h3>Cash Flow</h3>
    <div class="stat-row"><span>Total Cash In</span><span>${fmtMoney(d.total_cash_in)} SAR</span></div>
    <div class="stat-row"><span>Total Cash Out</span><span>${fmtMoney(d.total_cash_out)} SAR</span></div>
    <div class="stat-row"><span>Net Cash Flow</span><span>${fmtMoney(d.net_cash_flow)} SAR</span></div></div>`;
  html += '<div class="card"><table><thead><tr><th>Month</th><th>Cash In</th><th>Cash Out</th><th>Net</th></tr></thead><tbody>';
  (d.months || []).forEach(m => {
    html += `<tr><td>${m.month}</td><td>${fmtMoney(m.cash_in)}</td><td>${fmtMoney(m.cash_out)}</td><td>${fmtMoney(m.net)}</td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

// ==================== VAT Report ====================
async function renderVatReport() {
  const d = await api('/reports/vat-report');
  let html = `<div class="card"><h3>VAT Report</h3>
    <div class="stat-row"><span>Output VAT Collected</span><span>${fmtMoney(d.output_vat_collected)} SAR</span></div>
    <div class="stat-row"><span>Input VAT Paid</span><span>${fmtMoney(d.input_vat_paid)} SAR</span></div>
    <div class="stat-row"><span>Net VAT Payable</span><span>${fmtMoney(d.net_vat_payable)} SAR</span></div></div>`;
  document.getElementById('content').innerHTML = html;
}

// ==================== Custom Report Builder ====================
async function renderCustomReports() {
  const [reports, sources] = await Promise.all([api('/reports/custom-reports?page_size=30'), api('/reports/custom-reports/sources')]);
  window.__reportSources = sources;
  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openCustomReportModal()">+ New Custom Report</button></div>
    <table><thead><tr><th>Name</th><th>Source</th><th>Actions</th></tr></thead><tbody>`;
  (reports.data || reports).forEach(r => {
    html += `<tr><td>${r.name}</td><td>${r.source}</td><td>
      <button class="btn btn-sm btn-outline" onclick="runCustomReport('${r.id}')">Run</button>
      <button class="btn btn-sm btn-outline" onclick="exportCustomReport('${r.id}', '${r.name.replace(/'/g,"")}')">Export CSV</button>
      <button class="btn btn-sm btn-outline" onclick="downloadExport('/reports/custom-reports/${r.id}/run?format=pdf', '${r.name.replace(/'/g,"")}.pdf')">Export PDF</button>
      <button class="btn btn-sm btn-outline" onclick="deleteCustomReport('${r.id}')">Delete</button></td></tr>`;
  });
  html += '</tbody></table></div><div id="custom-report-results"></div>';
  document.getElementById('content').innerHTML = html;
}

function openCustomReportModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  const sourceOptions = Object.keys(window.__reportSources || {}).map(s => `<option value="${s}">${s}</option>`).join('');
  overlay.innerHTML = `<div class="modal"><h3>New Custom Report</h3><form id="cr-form">
    <div class="field"><label>Name</label><input name="name" required></div>
    <div class="field"><label>Source</label><select name="source" id="cr-source" required onchange="updateCrColumns()">${sourceOptions}</select></div>
    <div class="field"><label>Columns (comma-separated)</label><input name="columns" id="cr-columns" placeholder="e.g. id,total,status" required></div>
    <div style="margin-top:16px;display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);

  document.getElementById('cr-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = Object.fromEntries(new FormData(e.target));
    try {
      await api('/reports/custom-reports', { method: 'POST', body: JSON.stringify({
        name: f.name, source: f.source, columns: f.columns.split(',').map(c => c.trim()).filter(Boolean),
      })});
      closeModal(); renderCustomReports();
    } catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}
function updateCrColumns() {
  const source = document.getElementById('cr-source').value;
  const cols = (window.__reportSources || {})[source] || [];
  document.getElementById('cr-columns').placeholder = cols.join(', ');
}

async function runCustomReport(id) {
  try {
    const rows = await api(`/reports/custom-reports/${id}/run`);
    let html = '<div class="card"><h3>Results</h3><table><thead><tr>';
    const cols = rows.length ? Object.keys(rows[0]) : [];
    cols.forEach(c => html += `<th>${c}</th>`);
    html += '</tr></thead><tbody>';
    rows.forEach(r => { html += '<tr>' + cols.map(c => `<td>${r[c] ?? ''}</td>`).join('') + '</tr>'; });
    html += '</tbody></table></div>';
    document.getElementById('custom-report-results').innerHTML = html;
  } catch (err) { alert(err.message); }
}

// Binary-safe authenticated download — api() always parses JSON, which doesn't work for CSV/PDF/XLSX bytes.
async function downloadExport(path, filename) {
  const res = await fetch(API + path, {
    headers: { 'Accept': '*/*', ...(token ? { Authorization: `Bearer ${token}` } : {}), ...(tenantId ? { 'X-Tenant-ID': tenantId } : {}) },
  });
  if (!res.ok) { alert('Export failed.'); return; }
  const blob = await res.blob();
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = filename;
  document.body.appendChild(a); a.click(); a.remove();
  URL.revokeObjectURL(url);
}

function exportCustomReport(id, name) {
  downloadExport(`/reports/custom-reports/${id}/run?format=csv`, `${(name || 'report').replace(/\s+/g,'_')}.csv`);
}

async function deleteCustomReport(id) {
  if (!confirm('Delete this custom report?')) return;
  try { await api(`/reports/custom-reports/${id}`, { method: 'DELETE' }); renderCustomReports(); }
  catch (err) { alert(err.message); }
}

// ==================== Scheduled Reports ====================
async function renderScheduledReports() {
  const [schedules, customReports] = await Promise.all([api('/reports/scheduled-reports?page_size=30'), api('/reports/custom-reports?page_size=100')]);
  window.__customReportOptions = (customReports.data || customReports).map(r => `<option value="${r.id}">${r.name}</option>`).join('');
  let html = `<div class="card"><div class="toolbar"><span></span><button class="btn btn-primary btn-sm" onclick="openScheduleModal()">+ New Schedule</button></div>
    <table><thead><tr><th>Name</th><th>Report</th><th>Frequency</th><th>Format</th><th>Next Run</th><th>Active</th><th>Actions</th></tr></thead><tbody>`;
  (schedules.data || schedules).forEach(s => {
    html += `<tr><td>${s.name}</td><td>${s.custom_report_name || '—'}</td><td>${s.frequency}</td><td>${s.format}</td>
      <td>${fmtDate(s.next_run_at)}</td><td>${s.is_active ? 'Yes' : 'No'}</td>
      <td><button class="btn btn-sm btn-outline" onclick="runScheduleNow('${s.id}')">Run Now</button></td></tr>`;
  });
  html += '</tbody></table></div>';
  document.getElementById('content').innerHTML = html;
}

function openScheduleModal() {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `<div class="modal"><h3>New Scheduled Report</h3><form id="sr-form">
    <div class="field"><label>Name</label><input name="name" required></div>
    <div class="field"><label>Custom Report</label><select name="custom_report_id" required>${window.__customReportOptions}</select></div>
    <div class="field"><label>Frequency</label><select name="frequency"><option value="daily">Daily</option><option value="weekly">Weekly</option><option value="monthly" selected>Monthly</option></select></div>
    <div class="field"><label>Format</label><select name="format"><option value="csv" selected>CSV</option><option value="pdf">PDF</option></select></div>
    <div class="field"><label>Recipients (comma-separated emails)</label><input name="recipients" required></div>
    <div style="margin-top:16px;display:flex;gap:8px;"><button class="btn btn-primary" type="submit">Create</button><button class="btn btn-outline" type="button" onclick="closeModal()">Cancel</button></div>
    <div class="error-text" id="modal-error"></div></form></div>`;
  document.body.appendChild(overlay);

  document.getElementById('sr-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const f = Object.fromEntries(new FormData(e.target));
    try {
      await api('/reports/scheduled-reports', { method: 'POST', body: JSON.stringify({
        name: f.name, custom_report_id: f.custom_report_id, frequency: f.frequency, format: f.format,
        recipients: f.recipients.split(',').map(r => r.trim()).filter(Boolean),
      })});
      closeModal(); renderScheduledReports();
    } catch (err) { document.getElementById('modal-error').textContent = err.message; }
  });
}

async function runScheduleNow(id) {
  try { await api(`/reports/scheduled-reports/${id}/run-now`, { method: 'POST' }); renderScheduledReports(); }
  catch (err) { alert(err.message); }
}

// ==================== Boot ====================
if (token && tenantId) { boot(); }
</script>
</body>
</html>
