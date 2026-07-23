<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SoudaCore — Super Admin Console</title>
<style>
  :root { color-scheme: light; }
  * { box-sizing: border-box; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; margin: 0; background: #f4f5f7; color: #1a1a2e; }
  header { background: #1a1a2e; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
  header h1 { font-size: 18px; margin: 0; }
  main { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
  .card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
  .metrics { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; }
  .metric-value { font-size: 28px; font-weight: 700; }
  .metric-label { font-size: 13px; color: #666; }
  table { width: 100%; border-collapse: collapse; font-size: 14px; }
  th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #eee; }
  .badge { padding: 3px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; }
  .badge-trial { background: #dbeafe; color: #1e40af; }
  .badge-active { background: #d1fae5; color: #065f46; }
  .badge-past_due { background: #fef3c7; color: #92400e; }
  .badge-suspended { background: #fee2e2; color: #991b1b; }
  .badge-cancelled { background: #e5e7eb; color: #374151; }
  button { cursor: pointer; border: none; border-radius: 6px; padding: 6px 12px; font-size: 13px; font-weight: 600; }
  .btn-suspend { background: #fee2e2; color: #991b1b; }
  .btn-reactivate { background: #d1fae5; color: #065f46; }
  #login-form input { display: block; width: 100%; margin-bottom: 10px; padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
  #login-form button { background: #1a1a2e; color: #fff; width: 100%; padding: 10px; }
  #error { color: #991b1b; font-size: 13px; margin-top: 8px; }
  #app { display: none; }
</style>
</head>
<body>

<header>
  <h1>SoudaCore — Super Admin Console</h1>
  <button id="logout-btn" style="display:none;background:#33334d;color:#fff;">Log out</button>
</header>

<main>
  <div class="card" id="login-card" style="max-width:360px;">
    <h2 style="margin-top:0;font-size:16px;">Platform Administrator Login</h2>
    <form id="login-form">
      <input type="email" id="email" placeholder="Email" required autocomplete="username">
      <input type="password" id="password" placeholder="Password" required autocomplete="current-password">
      <button type="submit">Log in</button>
    </form>
    <div id="error"></div>
  </div>

  <div id="app">
    <div class="card">
      <h2 style="margin-top:0;font-size:16px;">Platform Metrics</h2>
      <div class="metrics" id="metrics"></div>
    </div>

    <div class="card">
      <h2 style="margin-top:0;font-size:16px;">Tenants</h2>
      <table>
        <thead>
          <tr><th>Company</th><th>Subdomain</th><th>Status</th><th>Users</th><th>Registered</th><th>Actions</th></tr>
        </thead>
        <tbody id="tenants-body"></tbody>
      </table>
    </div>
  </div>
</main>

<script>
// Deliberately vanilla JS, no build step — this is the one page in the
// whole product with a frontend, and it exists to talk to the same
// /api/v1 JSON API every other client uses. See docs/SUPER_ADMIN_CONSOLE.md
// for why this is scoped exactly this narrowly.
const API = '/api/v1';
let accessToken = null;

const $ = (id) => document.getElementById(id);

async function api(path, options = {}) {
  const res = await fetch(API + path, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      ...(accessToken ? { Authorization: `Bearer ${accessToken}` } : {}),
      ...(options.headers || {}),
    },
  });
  const body = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(body.message || 'Request failed');
  return body.data;
}

function statusBadge(status) {
  return `<span class="badge badge-${status}">${status}</span>`;
}

async function loadDashboard() {
  const metrics = await api('/admin/platform/metrics');
  $('metrics').innerHTML = `
    <div><div class="metric-value">${metrics.tenants.total}</div><div class="metric-label">Total Tenants</div></div>
    <div><div class="metric-value">${metrics.tenants.by_status.active}</div><div class="metric-label">Active</div></div>
    <div><div class="metric-value">${metrics.tenants.by_status.trial}</div><div class="metric-label">Trial</div></div>
    <div><div class="metric-value">${metrics.tenants.by_status.suspended}</div><div class="metric-label">Suspended</div></div>
    <div><div class="metric-value">${metrics.users_total}</div><div class="metric-label">Total Users</div></div>
    <div><div class="metric-value">${metrics.leads_total}</div><div class="metric-label">Total Leads</div></div>
    <div><div class="metric-value">${metrics.new_tenants_this_month}</div><div class="metric-label">New This Month</div></div>
  `;

  const tenants = await api('/admin/platform/tenants?page_size=50');
  $('tenants-body').innerHTML = tenants.map((t) => `
    <tr>
      <td>${t.name}</td>
      <td>${t.subdomain}</td>
      <td>${statusBadge(t.status)}</td>
      <td>${t.user_count ?? '—'}</td>
      <td>${new Date(t.created_at).toLocaleDateString()}</td>
      <td>
        ${t.status === 'suspended'
          ? `<button class="btn-reactivate" data-reactivate="${t.id}">Reactivate</button>`
          : `<button class="btn-suspend" data-suspend="${t.id}">Suspend</button>`}
      </td>
    </tr>
  `).join('');
}

$('login-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  $('error').textContent = '';
  try {
    const data = await api('/admin/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email: $('email').value, password: $('password').value }),
    });
    accessToken = data.access_token;
    $('login-card').style.display = 'none';
    $('app').style.display = 'block';
    $('logout-btn').style.display = 'inline-block';
    await loadDashboard();
  } catch (err) {
    $('error').textContent = err.message;
  }
});

$('logout-btn').addEventListener('click', () => {
  accessToken = null;
  $('app').style.display = 'none';
  $('login-card').style.display = 'block';
  $('logout-btn').style.display = 'none';
});

document.body.addEventListener('click', async (e) => {
  const suspendId = e.target.dataset.suspend;
  const reactivateId = e.target.dataset.reactivate;

  if (suspendId) {
    const reason = prompt('Reason for suspending this tenant:');
    if (!reason) return;
    await api(`/admin/platform/tenants/${suspendId}/suspend`, { method: 'POST', body: JSON.stringify({ reason }) });
    await loadDashboard();
  }

  if (reactivateId) {
    if (!confirm('Reactivate this tenant?')) return;
    await api(`/admin/platform/tenants/${reactivateId}/reactivate`, { method: 'POST' });
    await loadDashboard();
  }
});
</script>

</body>
</html>
