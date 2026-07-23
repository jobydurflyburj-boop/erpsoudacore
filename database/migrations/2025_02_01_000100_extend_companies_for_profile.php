<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Adds the remaining Company Profile fields from the Platform
    // Administration brief. legal_name/trade_name/cr_number/vat_number/
    // industry/logo_path/is_default already existed from the foundation.
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_name_ar')->nullable()->after('legal_name');
            $table->string('national_address')->nullable()->after('vat_number');
            $table->string('email')->nullable()->after('national_address');
            $table->string('phone', 30)->nullable()->after('email');
            $table->string('website')->nullable()->after('phone');
            $table->string('timezone', 64)->default('Asia/Riyadh')->after('website');
            $table->string('currency', 3)->default('SAR')->after('timezone');
            $table->string('language', 5)->default('ar')->after('currency');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1)->after('language');
            $table->string('business_type', 60)->nullable()->after('fiscal_year_start_month');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name_ar', 'national_address', 'email', 'phone', 'website',
                'timezone', 'currency', 'language', 'fiscal_year_start_month', 'business_type',
            ]);
        });
    }
};
