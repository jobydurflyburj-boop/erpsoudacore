<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Generic, reusable per-tenant sequential-number generator — backs
 * `leads.lead_number` today, and is the mechanism any future module
 * needing a human-readable sequential number (invoice, PO, etc.) reuses
 * rather than inventing its own. Atomic via a single UPDATE...RETURNING
 * (with an upsert for the first call) — safe under concurrent requests
 * without needing an application-level lock, since the row-level lock
 * implicit in the UPDATE serializes concurrent callers for the same
 * (tenant, name) pair.
 */
class SequenceService
{
    public function next(string $tenantId, string $name, string $prefix, int $padLength = 6): string
    {
        $value = DB::transaction(function () use ($tenantId, $name) {
            DB::statement('
                INSERT INTO sequence_counters (id, tenant_id, name, next_value, created_at, updated_at)
                VALUES (gen_random_uuid(), ?, ?, 1, now(), now())
                ON CONFLICT (tenant_id, name) DO NOTHING
            ', [$tenantId, $name]);

            $row = DB::selectOne('
                UPDATE sequence_counters
                SET next_value = next_value + 1, updated_at = now()
                WHERE tenant_id = ? AND name = ?
                RETURNING next_value - 1 AS value
            ', [$tenantId, $name]);

            return (int) $row->value;
        });

        return $prefix.'-'.str_pad((string) $value, $padLength, '0', STR_PAD_LEFT);
    }
}
