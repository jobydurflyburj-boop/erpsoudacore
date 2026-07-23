<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // The Platform Administration "Activity Log" screen needs module
    // attribution and parsed browser info, neither of which the
    // foundation's activity_logs table carried (it only needed
    // user_agent as a raw string for auth events). Adding both here
    // rather than creating a parallel table — one activity feed, not two.
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('module', 40)->nullable()->after('event');
            $table->string('browser', 100)->nullable()->after('user_agent');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['module', 'browser']);
        });
    }
};
