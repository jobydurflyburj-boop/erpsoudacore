<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OpportunityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'opportunity_number' => $this->opportunity_number,
            'name' => $this->name,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'lead_id' => $this->lead_id,
            'stage' => new OpportunityStageResource($this->whenLoaded('stage')),
            'amount' => $this->amount !== null ? (float) $this->amount : null,
            'probability' => $this->probability,
            'weighted_value' => $this->weightedValue(),
            'expected_close_date' => $this->expected_close_date?->toDateString(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'assignee' => new UserResource($this->whenLoaded('assignee')),
            'priority' => $this->priority,
            'notes' => $this->notes,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
