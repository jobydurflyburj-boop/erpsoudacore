<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuotationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'opportunity_id' => $this->opportunity_id,
            'document_date' => $this->document_date?->toDateString(),
            'subtotal' => (float) $this->subtotal, 'vat_amount' => (float) $this->vat_amount, 'total' => (float) $this->total,
            'notes' => $this->notes,
            'items' => QuotationItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
