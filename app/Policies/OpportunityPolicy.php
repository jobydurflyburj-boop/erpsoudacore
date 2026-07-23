<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

/**
 * Mirrors LeadPolicy/CustomerPolicy exactly — Sales sees/edits only
 * opportunities assigned to them, Owner/Admin/Manager see and manage
 * every opportunity.
 */
class OpportunityPolicy
{
    public function view(User $user, Opportunity $opportunity): bool
    {
        return $this->canAccessRecord($user, $opportunity);
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        return $this->canAccessRecord($user, $opportunity);
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return $this->canAccessRecord($user, $opportunity);
    }

    public function assign(User $user, Opportunity $opportunity): bool
    {
        return $this->canAccessRecord($user, $opportunity);
    }

    private function canAccessRecord(User $user, Opportunity $opportunity): bool
    {
        if (! in_array($user->role?->code, Opportunity::OWN_RECORDS_ONLY_ROLES, true)) {
            return true;
        }

        return $opportunity->assigned_to_user_id === $user->id;
    }
}
