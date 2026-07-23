<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_number' => $this->lead_number,
            'company_name' => $this->company_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'arabic_name' => $this->arabic_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'country' => $this->country,
            'city' => $this->city,
            'source' => new LeadSourceResource($this->whenLoaded('source')),
            'status' => new LeadStatusResource($this->whenLoaded('status')),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'expected_revenue' => $this->expected_revenue !== null ? (float) $this->expected_revenue : null,
            'probability' => $this->probability,
            'weighted_value' => $this->expected_revenue !== null
                ? round(((float) $this->expected_revenue) * $this->probability / 100, 2)
                : null,
            'priority' => $this->priority,
            'notes' => $this->notes,
            'is_converted' => $this->isConverted(),
            'converted_to_customer_id' => $this->converted_to_customer_id,
            'converted_at' => $this->converted_at?->toIso8601String(),
            'attachments_count' => $this->when(isset($this->attachments_count), fn () => $this->attachments_count),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
