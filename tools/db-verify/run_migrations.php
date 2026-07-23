<?php
/**
 * Runs every migration in database/migrations/ against a real PostgreSQL
 * database using schema_shim.php's minimal Schema/Blueprint/DB
 * compatibility layer — for environments (like this project's sandbox)
 * where `composer install` cannot reach Packagist. This is NOT a
 * replacement for `php artisan migrate` — it verifies the DATABASE layer
 * only (DDL, constraints, RLS policies), not application/HTTP behavior.
 * See docs/DATABASE_VERIFICATION.md for what this did and didn't prove.
 *
 * Usage: php tools/db-verify/run_migrations.php
 * Requires: php-pgsql extension, a reachable Postgres instance, and the
 * connection details below (adjust for your environment — these default
 * to a local throwaway verification database, not the app's real one).
 */
// Guard against the collision schema_shim.php's own docblock warns
// about: if this process somehow already has the real framework loaded
// (e.g. accidentally required from within a booted Laravel app), the
// shim's class declarations would fatal-collide with it. Checked here,
// not inside schema_shim.php itself, because PHP compiles unconditional
// top-level class declarations before any runtime code in the same file
// runs — a self-check inside the shim can never see its own collision
// in time.
if (class_exists(\Illuminate\Support\Facades\DB::class, false)) {
    fwrite(STDERR, "Refusing to run: the real Laravel framework is already loaded in this process.\n");
    fwrite(STDERR, "This tool is for standalone use only — see tools/db-verify/README.md.\n");
    exit(1);
}

require __DIR__ . '/schema_shim.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$host = getenv('VERIFY_DB_HOST') ?: '127.0.0.1';
$db = getenv('VERIFY_DB_NAME') ?: 'soudacore_verify';
$user = getenv('VERIFY_DB_USER') ?: 'soudacore';
$pass = getenv('VERIFY_DB_PASS') ?: 'secret';

$pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
DB::$pdo = $pdo;
Schema::$pdo = $pdo;

$dir = dirname(__DIR__, 2) . '/database/migrations';
$files = glob("$dir/*.php");
sort($files);

$results = [];
foreach ($files as $file) {
    $name = basename($file);
    try {
        $migration = include $file;
        $migration->up();
        $results[] = ['OK', $name, null];
        fwrite(STDOUT, "OK    $name\n");
    } catch (\Throwable $e) {
        $results[] = ['FAIL', $name, $e->getMessage()];
        fwrite(STDOUT, "FAIL  $name\n      " . $e->getMessage() . "\n");
    }
}

$ok = count(array_filter($results, fn($r) => $r[0] === 'OK'));
$fail = count($results) - $ok;
fwrite(STDOUT, "\n=== $ok/" . count($results) . " migrations ran cleanly, $fail failed ===\n");
