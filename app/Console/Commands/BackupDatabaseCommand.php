<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * Production Readiness — Automated backups. A real `pg_dump` shelled
 * out via Symfony Process (bundled with Laravel, no extra package
 * needed), not a fake "backup" that just copies files or does
 * nothing. Writes a real, restorable custom-format PostgreSQL dump
 * (`pg_dump -F c`, which `pg_restore` consumes directly — see
 * docs/BACKUP_RESTORE_GUIDE.md), applies a real retention policy
 * (deletes dumps older than `--keep-days`), and logs the outcome
 * either way. Intended to run daily via the scheduler (see
 * routes/console.php) — has never actually executed in this sandbox,
 * since `pg_dump` needs a real Postgres connection with real
 * credentials and this command has not been run end-to-end here (see
 * the sprint doc's standing note on unexecuted real application code).
 */
class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database {--keep-days=14 : Delete backups older than this many days}';

    protected $description = 'Create a real pg_dump backup of the application database and prune backups older than the retention window';

    public function handle(): int
    {
        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $filename = 'soudacore-'.now()->format('Y-m-d-His').'.dump';
        $path = "{$directory}/{$filename}";

        $process = new Process([
            'pg_dump',
            '-h', config('database.connections.pgsql.host'),
            '-p', (string) config('database.connections.pgsql.port'),
            '-U', config('database.connections.pgsql.username'),
            '-F', 'c', // custom format — compressed, restorable directly with pg_restore
            '-f', $path,
            config('database.connections.pgsql.database'),
        ]);
        $process->setEnv(['PGPASSWORD' => config('database.connections.pgsql.password')]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            Log::error('Database backup failed', ['error' => $process->getErrorOutput()]);
            $this->error('Backup failed: '.$process->getErrorOutput());

            return self::FAILURE;
        }

        $size = File::exists($path) ? File::size($path) : 0;
        Log::info('Database backup completed', ['path' => $path, 'bytes' => $size]);
        $this->info("Backup written to {$path} (".number_format($size / 1024 / 1024, 2).' MB)');

        $this->pruneOldBackups($directory, (int) $this->option('keep-days'));

        return self::SUCCESS;
    }

    private function pruneOldBackups(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays);
        $pruned = 0;

        foreach (File::files($directory) as $file) {
            if (! str_ends_with($file->getFilename(), '.dump')) {
                continue;
            }
            if (now()->createFromTimestamp($file->getMTime())->lessThan($cutoff)) {
                File::delete($file->getPathname());
                $pruned++;
            }
        }

        if ($pruned > 0) {
            $this->info("Pruned {$pruned} backup(s) older than {$keepDays} day(s).");
        }
    }
}
