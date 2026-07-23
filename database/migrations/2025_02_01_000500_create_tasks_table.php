<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Lightweight, platform-level task/reminder list — backs the
    // Dashboard's "Tasks" widget. Deliberately NOT a project-management
    // module (no boards/projects/subtasks) — that would be its own
    // future module. This is the same class of thing as "Pending
    // Approvals": a simple, real, queryable list, not a placeholder.
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->uuid('assigned_to_user_id');
            $table->uuid('created_by_user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('priority', 10)->default('normal'); // low|normal|high
            $table->string('status', 15)->default('pending'); // pending|in_progress|completed|cancelled
            $table->timestampTz('due_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'assigned_to_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
