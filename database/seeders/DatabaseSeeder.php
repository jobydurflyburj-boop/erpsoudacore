<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,   // must run first — roles reference permissions
            SuperAdminSeeder::class,
            DemoTenantSeeder::class,   // local/dev only, no-ops elsewhere
        ]);
    }
}
