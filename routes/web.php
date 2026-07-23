<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| This product is, and remains, a JSON API (routes/api.php). Two static
| HTML shells are served from here, both talking to the same /api/v1
| endpoints via fetch() with a bearer token like any other API consumer:
|
| - /super-admin — the platform operator console (scoped narrowly on
|   purpose, see docs/SUPER_ADMIN_CONSOLE.md).
| - /app — the tenant-facing MVP demo console (Dashboard, CRM, Sales,
|   Purchase, Inventory, Accounting, Reports, AI Assistant, Users/Roles,
|   Company Settings). Built for the client-ready MVP demo — see
|   docs/MVP_DEMO.md for exactly what this does and doesn't cover, and
|   why this is still not read as a permanent project-wide frontend
|   architecture decision (framework, build tooling, etc. remain open).
*/

Route::view('/super-admin', 'super-admin.console')->name('super-admin.console');
Route::view('/app', 'app.console')->name('app.console');
