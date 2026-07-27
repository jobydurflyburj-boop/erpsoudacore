<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_registration_requests', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('legal_name');
            $table->string('subdomain');
            $table->string('trade_name')->nullable();
            $table->string('cr_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('admin_full_name');
            $table->string('admin_email');
            $table->string('admin_password_hash');
            $table->string('default_locale', 5)->default('ar');
            $table->string('status', 20)->default('pending'); // pending|approved|rejected
            $table->uuid('tenant_id')->nullable(); // set once approved
            $table->uuid('reviewed_by')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_registration_requests');
    }
};
