<?php

namespace Database\Seeders;

use App\Services\RegistrationService;
use Illuminate\Database\Seeder;

/**
 * For local/dev environments ONLY — creates one working tenant with a
 * real Company Owner login so a developer or QA engineer has something
 * to log into immediately after `php artisan migrate:fresh --seed`. This
 * is registration-flow data (a real account) — DemoDataSeeder (called
 * below) adds a small, genuinely linked set of real business records
 * across CRM/Inventory/Purchase using the same real services every
 * controller uses, so the demo tenant has something real to look at
 * beyond just a login.
 */
class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $result = app(RegistrationService::class)->registerCompany([
            'legal_name' => 'Demo Trading Co.',
            'subdomain' => 'demo',
            'admin_full_name' => 'Demo Owner',
            'admin_email' => 'owner@demo.soudacore.app',
            'admin_password' => 'DemoPassword!123',
            'admin_password_confirmation' => 'DemoPassword!123',
            'default_locale' => 'ar',
        ]);

        app(DemoDataSeeder::class)->run($result['tenant'], $result['user']);
    }
}
