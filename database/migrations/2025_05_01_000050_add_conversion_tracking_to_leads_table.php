<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Forward reference paired with customers.source_lead_id (the
    // reverse) — lets "has this lead been converted, and to which
    // customer" be answered directly from the lead row without a
    // reverse lookup query. Nullable: most leads are never converted
    // (lost, or still in progress).
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->uuid('converted_to_customer_id')->nullable()->after('lead_status_id');
            $table->timestampTz('converted_at')->nullable()->after('converted_to_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['converted_to_customer_id', 'converted_at']);
        });
    }
};
