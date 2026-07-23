<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Supports issuing an opaque "login ticket" instead of ever exposing
    // a raw user_id to an unauthenticated client between the
    // password step and the OTP step — see OtpService::generateLoginTicket
    // and docs/FOUNDATION.md "Tenant isolation review — OTP ticket".
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('ticket_hash', 64)->nullable()->unique()->after('code_hash');
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn('ticket_hash');
        });
    }
};
