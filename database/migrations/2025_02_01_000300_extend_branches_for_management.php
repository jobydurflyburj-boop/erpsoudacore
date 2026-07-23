<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->uuid('manager_user_id')->nullable()->after('company_id');
            $table->string('phone', 30)->nullable()->after('address');
            $table->jsonb('working_hours')->nullable()->after('phone'); // {"sun":{"open":"09:00","close":"18:00"},...}
            $table->decimal('latitude', 10, 7)->nullable()->after('working_hours');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            $table->foreign('manager_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropForeign(['manager_user_id']);
            $table->dropColumn(['manager_user_id', 'phone', 'working_hours', 'latitude', 'longitude']);
        });
    }
};
