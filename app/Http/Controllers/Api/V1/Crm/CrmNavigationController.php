<?php

namespace App\Http\Controllers\Api\V1\Crm;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Same pattern as DashboardService::quickActions — a small, permission-
 * filtered catalog the frontend renders as the CRM module's side nav,
 * rather than the frontend hardcoding a menu that doesn't reflect what
 * the current user can actually do. No frontend framework decision is
 * implied by this endpoint; it's just data.
 */
class CrmNavigationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $items = [
            ['key' => 'crm_dashboard', 'label' => 'CRM Dashboard', 'route' => '/crm/dashboard', 'permission' => 'crm.view'],
            ['key' => 'leads', 'label' => 'Leads', 'route' => '/crm/leads', 'permission' => 'crm.view'],
            ['key' => 'customers', 'label' => 'Customers', 'route' => '/crm/customers', 'permission' => 'crm.view'],
            ['key' => 'opportunities', 'label' => 'Opportunities', 'route' => '/crm/opportunities', 'permission' => 'crm.view'],
            ['key' => 'lead_sources', 'label' => 'Lead Sources', 'route' => '/crm/lead-sources', 'permission' => 'crm.edit'],
            ['key' => 'lead_statuses', 'label' => 'Lead Statuses', 'route' => '/crm/lead-statuses', 'permission' => 'crm.edit'],
        ];

        $filtered = array_values(array_filter($items, function ($item) use ($user) {
            [$module, $action] = explode('.', $item['permission'], 2);

            return $user->role?->hasPermission($module, $action) ?? false;
        }));

        return $this->ok($filtered);
    }
}
