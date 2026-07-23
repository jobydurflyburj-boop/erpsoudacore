<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleProvisioningService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Provisions the platform Super Admin role + one bootstrap Super Admin
 * account, so there's always at least one way into the Super Admin
 * console on a fresh environment. The bootstrap password MUST be rotated
 * immediately after first login in any real environment — this seeder is
 * for getting a deployable environment off the ground, not for ongoing
 * account management (use the admin console/API from here on).
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $role = app(RoleProvisioningService::class)->provisionSuperAdminRole();

        User::withoutGlobalScope('tenant')->firstOrCreate(
            ['tenant_id' => null, 'email' => 'superadmin@soudacore.app'],
            [
                'role_id' => $role->id,
                'password' => Hash::make(env('SUPER_ADMIN_BOOTSTRAP_PASSWORD', 'ChangeMe!12345')),
                'full_name' => 'Platform Super Admin',
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'preferred_locale' => 'en',
                'timezone' => 'Asia/Riyadh',
            ]
        );
    }
}
