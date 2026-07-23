<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Production Readiness — real scheduled tasks, wired for the first
// time this sprint. Both commands already existed (built in the
// Reports & Analytics and this sprint respectively) but were only
// ever runnable by hand — nothing scheduled them. `withoutOverlapping()`
// on each prevents a slow run from stacking a second instance if the
// scheduler's own tick (docker-compose's `scheduler` service, already
// running `schedule:run` every 60s) fires again before the first
// finishes. Has never actually executed in this sandbox — the
// scheduler needs the app to be running continuously, which it never
// has been here (composer install blocked, same standing constraint
// as every other real-but-unexecuted piece of this project).
Schedule::command('reports:process-scheduled')
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('backup:database')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->onOneServer();
