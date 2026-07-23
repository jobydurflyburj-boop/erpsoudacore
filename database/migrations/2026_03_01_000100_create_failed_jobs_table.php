<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Production Readiness: a real gap found during this sprint's
    // review — QUEUE_CONNECTION=redis has been configured since the
    // Foundation sprint (docker-compose.yml already runs a queue
    // worker), but no `failed_jobs` table ever existed to record a
    // job that exhausts its retries, meaning `queue:work` would only
    // ever be able to log a failure to the application log, with no
    // `queue:failed` / `queue:retry` recovery path — a real
    // reliability gap for a worker now running two real queued
    // Mailables (NotificationMail, ScheduledReportMail — both fixed
    // to actually implement ShouldQueue in this same sprint; they
    // were declared queueable via the Queueable trait but never
    // implemented the ShouldQueue interface, so they were being sent
    // synchronously despite the queue infrastructure existing).
    // Laravel's own standard schema for this table — not
    // reduced or altered.
    public function up(): void
    {
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
