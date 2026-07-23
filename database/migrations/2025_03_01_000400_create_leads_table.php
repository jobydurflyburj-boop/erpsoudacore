<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('lead_number', 30); // SequenceService-generated, e.g. LD-000123

            $table->string('company_name')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('arabic_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('country', 2)->nullable(); // ISO 3166-1 alpha-2
            $table->string('city')->nullable();

            $table->uuid('lead_source_id')->nullable();
            $table->uuid('lead_status_id');
            $table->uuid('assigned_to_user_id')->nullable();

            $table->decimal('expected_revenue', 14, 2)->nullable();
            $table->unsignedTinyInteger('probability')->default(0); // 0-100
            $table->string('priority', 10)->default('normal'); // low|normal|high|urgent

            $table->text('notes')->nullable();

            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('updated_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('lead_source_id')->references('id')->on('lead_sources')->nullOnDelete();
            $table->foreign('lead_status_id')->references('id')->on('lead_statuses')->restrictOnDelete();
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['tenant_id', 'lead_number']);
            $table->index(['tenant_id', 'lead_status_id']);
            $table->index(['tenant_id', 'assigned_to_user_id']);
            $table->index(['tenant_id', 'lead_source_id']);
            $table->index(['tenant_id', 'priority']);
            $table->index(['tenant_id', 'created_at']);
        });

        // Case-insensitive email lookups/uniqueness-checking, same as users.email.
        DB::statement('ALTER TABLE leads ALTER COLUMN email TYPE citext');
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
