<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Distinct from TenantResource — that one is what a tenant's own users
 * see about their own account; this one is the richer, cross-tenant
 * view a Super Admin needs (user counts, suspension metadata), which
 * would be an information-disclosure mismatch if reused for the
 * tenant-facing endpoint.
 */
class PlatformTenantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'subdomain' => $this->subdomain,
            'status' => $this->status,
            'default_locale' => $this->default_locale,
            'default_currency' => $this->default_currency,
            'timezone' => $this->timezone,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'suspended_at' => $this->suspended_at?->toIso8601String(),
            'suspension_reason' => $this->suspension_reason,
            'suspended_by' => new UserResource($this->whenLoaded('suspendedBy')),
            'user_count' => $this->when(isset($this->users_count), fn () => $this->users_count),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
