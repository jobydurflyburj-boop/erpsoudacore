<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // CRM Sprint 2 — the "a won lead should become something" gap
    // flagged in docs/ROADMAP.md. A Customer is the ongoing-relationship
    // entity a Lead converts into once won; see LeadConversionService.
    // source_lead_id is nullable — a Customer can also be created
    // directly (walk-in / referred without ever being a tracked Lead),
    // which is why this isn't just a status flag added to `leads`.
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('customer_number', 30); // SequenceService-generated, e.g. CU-000123 — same reusable mechanism as Lead numbers

            $table->string('customer_type', 10)->default('company'); // company|individual
            $table->string('company_name')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('arabic_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->string('vat_number', 20)->nullable(); // ZATCA 15-digit VAT number, when the customer is itself a VAT-registered business

            $table->uuid('account_manager_user_id')->nullable();
            $table->string('status', 15)->default('active'); // active|inactive
            $table->decimal('credit_limit', 14, 2)->default(0);
            $table->unsignedSmallInteger('payment_terms_days')->default(30);
            $table->text('notes')->nullable();

            $table->uuid('source_lead_id')->nullable(); // the Lead this Customer was converted from, if any

            $table->uuid('created_by_user_id')->nullable();
            $table->uuid('updated_by_user_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('account_manager_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('source_lead_id')->references('id')->on('leads')->nullOnDelete();
            $table->foreign('created_by_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id')->references('id')->on('users')->nullOnDelete();

            $table->unique(['tenant_id', 'customer_number']);
            $table->index(['tenant_id', 'account_manager_user_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'customer_type']);
        });

        DB::statement('ALTER TABLE customers ALTER COLUMN email TYPE citext');
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
