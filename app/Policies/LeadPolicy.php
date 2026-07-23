<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

/**
 * Complements — does not replace — the route-level 'permission:crm.*'
 * middleware. That middleware answers "can this role touch leads at
 * all"; this policy answers the record-level question the foundation's
 * docs described but never needed until now: for Lead::OWN_RECORDS_ONLY_ROLES
 * (Sales), "view"/"update"/"delete" are further narrowed to leads
 * assigned to them. Owner/Admin/Manager see and manage every lead.
 *
 * Both checks run: CheckPermission middleware first (fast, no DB row
 * needed), then this policy once a specific Lead is loaded.
 */
class LeadPolicy
{
    public function view(User $user, Lead $lead): bool
    {
        return $this->canAccessRecord($user, $lead);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->canAccessRecord($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        // Deletion is already restricted to Owner/Admin/Manager at the
        // route level (crm.delete isn't granted to Sales at all — see
        // RoleProvisioningService) — this exists for defense in depth,
        // not because Sales could otherwise reach here.
        return $this->canAccessRecord($user, $lead);
    }

    public function assign(User $user, Lead $lead): bool
    {
        return $this->canAccessRecord($user, $lead);
    }

    private function canAccessRecord(User $user, Lead $lead): bool
    {
        if (! in_array($user->role?->code, Lead::OWN_RECORDS_ONLY_ROLES, true)) {
            return true; // back-office roles (Owner/Admin/Manager) see everything
        }

        return $lead->assigned_to_user_id === $user->id;
    }
}
