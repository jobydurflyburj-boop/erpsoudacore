<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Split from the column-add migration (2025_05_01_000050) because
    // the FK target (customers) doesn't exist until 2025_05_01_000100 —
    // same ordering constraint every cross-module FK in this codebase
    // has followed.
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('converted_to_customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['converted_to_customer_id']);
        });
    }
};
