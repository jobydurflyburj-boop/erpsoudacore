<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name_en' => $this->name_en,
            'name_ar' => $this->name_ar,
            'description' => $this->description,
            'manager' => new UserResource($this->whenLoaded('manager')),
            'is_active' => $this->is_active,
            'user_count' => $this->when(isset($this->users_count), fn () => $this->users_count),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
