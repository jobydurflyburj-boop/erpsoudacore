<?php
/**
 * Minimal Schema/Blueprint/DB compatibility shim — NOT a reimplementation
 * of Laravel, just enough of the Schema Builder's public surface (the
 * exact methods this project's 39 migrations actually call, enumerated
 * by grep beforehand) to let the REAL migration files run verbatim
 * against real PostgreSQL, without needing the full framework (blocked
 * in this sandbox: Packagist returns 403, outside the network policy's
 * allowed domains — an environment constraint, not a code defect).
 *
 * This exists to verify the DATABASE layer for real. It does NOT
 * verify application/HTTP behavior — that still requires the full
 * framework and is out of scope for what's achievable here.
 *
 * ⚠️  SAFETY: this file declares classes in the `Illuminate\...`
 * namespace that WILL FATAL-COLLIDE with the real framework if this is
 * ever `require`'d in a process that has also loaded `vendor/autoload.php`.
 * It is deliberately kept outside `app/` and out of composer.json's
 * autoload paths so Composer's own autoloader never touches it. The
 * actual guard against this lives in `run_migrations.php` (the only
 * intended caller) — a self-check here doesn't work: PHP compiles
 * unconditional top-level class declarations before any runtime code in
 * the same file executes, so by the time a check here could run, this
 * file's own classes already exist regardless of collision status.
 */

namespace Illuminate\Database\Migrations { class Migration {} }

namespace Illuminate\Support\Facades {
    class DB {
        public static \PDO $pdo;
        public static function statement(string $sql, array $bindings = []): bool {
            $stmt = self::$pdo->prepare($sql);
            return $stmt->execute($bindings);
        }
        public static function unprepared(string $sql): bool {
            return self::$pdo->exec($sql) !== false;
        }
        public static function select(string $sql, array $bindings = []): array {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($bindings);
            return $stmt->fetchAll(\PDO::FETCH_OBJ);
        }
        public static function selectOne(string $sql, array $bindings = []) {
            $rows = self::select($sql, $bindings);
            return $rows[0] ?? null;
        }
        public static function raw(string $sql): RawExpr { return new RawExpr($sql); }
    }
    class RawExpr { public function __construct(public string $sql) {} public function __toString(): string { return $this->sql; } }

    class Schema {
        public static \PDO $pdo;
        public static function create(string $table, \Closure $cb): void {
            $bp = new \Illuminate\Database\Schema\Blueprint($table);
            $cb($bp);
            foreach ($bp->compileCreate() as $sql) { self::$pdo->exec($sql); }
        }
        public static function table(string $table, \Closure $cb): void {
            $bp = new \Illuminate\Database\Schema\Blueprint($table);
            $cb($bp);
            foreach ($bp->compileAlter() as $sql) { self::$pdo->exec($sql); }
        }
        public static function dropIfExists(string $table): void {
            self::$pdo->exec("DROP TABLE IF EXISTS \"$table\" CASCADE");
        }
    }
}

namespace Illuminate\Database\Schema {

    class ColumnDefinition {
        public bool $isNullable = false;
        public bool $isUnique = false;
        public bool $isPrimary = false;
        public $defaultValue = null;
        public bool $hasDefault = false;
        public function __construct(public string $name, public string $sqlType) {}
        public function nullable($v = true) { $this->isNullable = $v; return $this; }
        public function default($v) { $this->hasDefault = true; $this->defaultValue = $v; return $this; }
        public function unique() { $this->isUnique = true; return $this; }
        public function primary() { $this->isPrimary = true; return $this; }
        public function after($col) { return $this; } // no-op — Postgres has no column ordering; Laravel ignores this on pgsql too
        public function change() { return $this; }
        // Added during the Production Readiness sprint: failed_jobs.failed_at
        // needed this, the same kind of real gap CRM Sprint 3 (date()),
        // the HR sprint (time()/dateTime()) each found and fixed rather
        // than worked around.
        public function useCurrent() { $this->hasDefault = true; $this->defaultValue = new \Illuminate\Support\Facades\RawExpr('CURRENT_TIMESTAMP'); return $this; }
        public function toSql(): string {
            $sql = "\"{$this->name}\" {$this->sqlType}";
            if (!$this->isNullable) $sql .= " NOT NULL";
            if ($this->hasDefault) {
                $d = $this->defaultValue;
                if ($d instanceof \Illuminate\Support\Facades\RawExpr) $sql .= " DEFAULT {$d->sql}";
                elseif (is_bool($d)) $sql .= " DEFAULT " . ($d ? "TRUE" : "FALSE");
                elseif (is_int($d) || is_float($d)) $sql .= " DEFAULT {$d}";
                else $sql .= " DEFAULT " . self::quote($d);
            }
            return $sql;
        }
        public static function quote(string $v): string { return "'" . str_replace("'", "''", $v) . "'"; }
    }

    class ForeignKeyDefinition {
        public string $refColumn = 'id';
        public string $refTable = '';
        public string $onDelete = 'NO ACTION';
        public function __construct(public Blueprint $bp, public string $column) {}
        public function references(string $col) { $this->refColumn = $col; return $this; }
        public function on(string $table) { $this->refTable = $table; $this->bp->foreignKeys[] = $this; return $this; }
        public function cascadeOnDelete() { $this->onDelete = 'CASCADE'; return $this; }
        public function nullOnDelete() { $this->onDelete = 'SET NULL'; return $this; }
        public function restrictOnDelete() { $this->onDelete = 'RESTRICT'; return $this; }
        public function toSql(string $table): string {
            $name = "{$table}_{$this->column}_foreign";
            return "ALTER TABLE \"$table\" ADD CONSTRAINT \"$name\" FOREIGN KEY (\"{$this->column}\") REFERENCES \"{$this->refTable}\"(\"{$this->refColumn}\") ON DELETE {$this->onDelete}";
        }
    }

    class Blueprint {
        public array $columns = [];
        public array $foreignKeys = [];
        public array $uniques = [];
        public array $indexes = [];
        public ?array $primaryCols = null;
        public array $dropColumns = [];
        public array $dropForeigns = [];
        public array $dropIndexes = [];

        public function __construct(public string $table) {}

        private function add(string $name, string $type): ColumnDefinition {
            $c = new ColumnDefinition($name, $type);
            $this->columns[] = $c;
            return $c;
        }

        public function id(string $name = 'id') { $c = $this->add($name, 'BIGSERIAL'); $c->primary(); return $c; }
        public function uuid(string $name) { return $this->add($name, 'UUID'); }
        public function string(string $name, int $len = 255) { return $this->add($name, "VARCHAR($len)"); }
        public function text(string $name) { return $this->add($name, 'TEXT'); }
        // Added during the Production Readiness sprint: failed_jobs
        // needed this — Postgres has no separate TEXT/LONGTEXT
        // distinction (TEXT is already unlimited), so this is a real,
        // correct mapping, not a workaround.
        public function longText(string $name) { return $this->add($name, 'TEXT'); }
        public function boolean(string $name) { return $this->add($name, 'BOOLEAN'); }
        public function decimal(string $name, int $p = 8, int $s = 2) { return $this->add($name, "DECIMAL($p,$s)"); }
        public function unsignedBigInteger(string $name) { return $this->add($name, 'BIGINT'); }
        public function unsignedSmallInteger(string $name) { return $this->add($name, 'SMALLINT'); }
        public function unsignedTinyInteger(string $name) { return $this->add($name, 'SMALLINT'); }
        public function jsonb(string $name) { return $this->add($name, 'JSONB'); }
        public function timestamp(string $name) { $c = $this->add($name, 'TIMESTAMP'); $c->nullable(); return $c; }
        public function timestampTz(string $name) { $c = $this->add($name, 'TIMESTAMPTZ'); $c->nullable(); return $c; }
        public function date(string $name) { return $this->add($name, 'DATE'); }
        // Added during the HR & Payroll sprint: shifts.start_time/end_time
        // and attendances.check_in/check_out needed these two column
        // types, which the shim didn't support yet — the same kind of
        // real gap CRM Sprint 3 found and fixed (a missing date()
        // method) rather than working around.
        public function time(string $name) { return $this->add($name, 'TIME'); }
        public function dateTime(string $name) { $c = $this->add($name, 'TIMESTAMP'); $c->nullable(); return $c; }
        public function timestamps() { $this->add('created_at', 'TIMESTAMPTZ')->nullable(); $this->add('updated_at', 'TIMESTAMPTZ')->nullable(); }
        public function softDeletes() { $this->add('deleted_at', 'TIMESTAMPTZ')->nullable(); }
        public function rememberToken() { $this->add('remember_token', 'VARCHAR(100)')->nullable(); }
        public function morphs(string $name) {
            $this->add("{$name}_type", 'VARCHAR(255)');
            $this->add("{$name}_id", 'UUID');
            $this->index(["{$name}_type", "{$name}_id"]);
        }

        public function foreign(string $col) { return new ForeignKeyDefinition($this, $col); }
        public function unique($cols, ?string $name = null) { $this->uniques[] = (array) $cols; }
        public function index($cols, ?string $name = null) { $this->indexes[] = (array) $cols; }
        public function primary($cols) { $this->primaryCols = (array) $cols; }
        public function dropColumn($cols) { $this->dropColumns = array_merge($this->dropColumns, (array) $cols); }
        public function dropForeign($cols) { $this->dropForeigns = array_merge($this->dropForeigns, (array) $cols); }
        public function dropIndex($name) { $this->dropIndexes[] = $name; }

        public function compileCreate(): array {
            $lines = array_map(fn($c) => $c->toSql(), $this->columns);
            $pkCols = array_map(fn($c) => $c->name, array_filter($this->columns, fn($c) => $c->isPrimary));
            if ($this->primaryCols) $pkCols = $this->primaryCols;
            if ($pkCols) $lines[] = "PRIMARY KEY (" . implode(',', array_map(fn($c) => "\"$c\"", $pkCols)) . ")";
            foreach ($this->uniques as $u) $lines[] = "UNIQUE (" . implode(',', array_map(fn($c) => "\"$c\"", $u)) . ")";
            foreach ($this->columns as $c) if ($c->isUnique) $lines[] = "UNIQUE (\"{$c->name}\")";

            $sqls = ["CREATE TABLE \"{$this->table}\" (" . implode(', ', $lines) . ")"];
            foreach ($this->indexes as $i => $cols) {
                $iname = "{$this->table}_" . implode('_', $cols) . "_index";
                $sqls[] = "CREATE INDEX \"$iname\" ON \"{$this->table}\" (" . implode(',', array_map(fn($c) => "\"$c\"", $cols)) . ")";
            }
            foreach ($this->foreignKeys as $fk) $sqls[] = $fk->toSql($this->table);
            return $sqls;
        }

        public function compileAlter(): array {
            $sqls = [];
            foreach ($this->columns as $c) {
                $sqls[] = "ALTER TABLE \"{$this->table}\" ADD COLUMN " . $c->toSql();
            }
            foreach ($this->foreignKeys as $fk) $sqls[] = $fk->toSql($this->table);
            foreach ($this->uniques as $u) {
                $iname = "{$this->table}_" . implode('_', $u) . "_unique";
                $sqls[] = "ALTER TABLE \"{$this->table}\" ADD CONSTRAINT \"$iname\" UNIQUE (" . implode(',', array_map(fn($c) => "\"$c\"", $u)) . ")";
            }
            foreach ($this->indexes as $cols) {
                $iname = "{$this->table}_" . implode('_', $cols) . "_index";
                $sqls[] = "CREATE INDEX \"$iname\" ON \"{$this->table}\" (" . implode(',', array_map(fn($c) => "\"$c\"", $cols)) . ")";
            }
            foreach ($this->dropForeigns as $col) {
                $sqls[] = "ALTER TABLE \"{$this->table}\" DROP CONSTRAINT IF EXISTS \"{$this->table}_{$col}_foreign\"";
            }
            foreach ($this->dropColumns as $col) {
                $sqls[] = "ALTER TABLE \"{$this->table}\" DROP COLUMN IF EXISTS \"$col\"";
            }
            foreach ($this->dropIndexes as $name) {
                $sqls[] = "DROP INDEX IF EXISTS \"$name\"";
            }
            return $sqls;
        }
    }
}
