<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

/**
 * Mirrors LeadPolicy exactly — same reasoning, same rule shape: Sales
 * sees/edits only their own assigned customers (account_manager_user_id),
 * Owner/Admin/Manager see and manage every customer. Complements, not
 * replaces, the route-level 'permission:crm.*' middleware.
 */
class CustomerPolicy
{
    public function view(User $user, Customer $customer): bool
    {
        return $this->canAccessRecord($user, $customer);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $this->canAccessRecord($user, $customer);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $this->canAccessRecord($user, $customer);
    }

    private function canAccessRecord(User $user, Customer $customer): bool
    {
        if (! in_array($user->role?->code, Customer::OWN_RECORDS_ONLY_ROLES, true)) {
            return true;
        }

        return $customer->account_manager_user_id === $user->id;
    }
}
