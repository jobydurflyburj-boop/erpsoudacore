<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');                       // subscriber-facing account name
            $table->string('subdomain')->unique();         // <subdomain>.soudacore.app
            $table->string('status', 20)->default('trial'); // trial|active|past_due|suspended|cancelled
            $table->uuid('subscription_plan_id')->nullable(); // FK added when billing module lands
            $table->string('default_locale', 5)->default('ar');
            $table->string('default_currency', 3)->default('SAR');
            $table->string('timezone', 64)->default('Asia/Riyadh');
            $table->timestampTz('trial_ends_at')->nullable();
            $table->timestampTz('suspended_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
