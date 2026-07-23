<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => $this->avatar_path ? Storage::disk('public')->url($this->avatar_path) : null,
            'status' => $this->status,
            'preferred_locale' => $this->preferred_locale,
            'timezone' => $this->timezone,
            'role' => new RoleResource($this->whenLoaded('role')),
            'company_id' => $this->company_id,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'default_branch_id' => $this->default_branch_id,
            'branches' => BranchResource::collection($this->whenLoaded('branches')),
            'mfa_enabled' => $this->mfaEnabled(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'last_login_ip' => $this->when($request->user()?->can('admin.view'), $this->last_login_ip),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
