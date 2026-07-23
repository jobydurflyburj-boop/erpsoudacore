<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_number' => $this->customer_number,
            'customer_type' => $this->customer_type,
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
            'address' => $this->address,
            'vat_number' => $this->vat_number,
            'account_manager' => new UserResource($this->whenLoaded('accountManager')),
            'status' => $this->status,
            'credit_limit' => (float) $this->credit_limit,
            'payment_terms_days' => $this->payment_terms_days,
            'notes' => $this->notes,
            'source_lead_id' => $this->source_lead_id,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updater' => new UserResource($this->whenLoaded('updater')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
