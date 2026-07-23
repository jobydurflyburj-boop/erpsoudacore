<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

/**
 * Seeds the global permission catalog from config/permissions.php — the
 * single source of truth referenced throughout (RoleProvisioningService,
 * CheckPermission middleware, the role-builder UI). Idempotent: safe to
 * run again after config/permissions.php gains a new module/action.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('permissions.modules') as $moduleKey => $module) {
            foreach ($module['actions'] as $action) {
                Permission::updateOrCreate(
                    ['name' => "{$moduleKey}.{$action}"],
                    [
                        'module' => $moduleKey,
                        'action' => $action,
                        'label' => ucfirst($action).' '.$module['label'],
                    ]
                );
            }
        }
    }
}
