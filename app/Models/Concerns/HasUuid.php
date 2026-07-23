<?php

namespace App\Models\Concerns;

trait HasUuid
{
    public function initializeHasUuid(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    // Postgres generates the UUID via gen_random_uuid() column default
    // (see migrations) — this trait just tells Eloquent not to expect an
    // auto-increment integer key. We deliberately do NOT generate the
    // UUID in PHP (e.g. via a `creating` event with Str::uuid()) so the
    // database remains the single source of truth for the value even
    // when a row is inserted outside the app (seeders, raw SQL, a future
    // ETL job).
}
