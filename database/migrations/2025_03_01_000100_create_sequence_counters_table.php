<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Generic, reusable per-tenant sequence counter — backs Lead Number
    // today (SequenceService), and is the same mechanism any future
    // module needing a human-readable sequential number (invoice number,
    // PO number, etc.) will reuse rather than reinventing its own
    // numbering scheme. One row per (tenant, sequence name).
    public function up(): void
    {
        Schema::create('sequence_counters', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('tenant_id');
            $table->string('name', 60); // 'lead_number' | future: 'invoice_number', 'po_number', ...
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_counters');
    }
};
