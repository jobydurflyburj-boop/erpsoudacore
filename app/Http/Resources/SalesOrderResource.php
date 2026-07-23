<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'quotation_id' => $this->quotation_id,
            'document_date' => $this->document_date?->toDateString(),
            'subtotal' => (float) $this->subtotal, 'vat_amount' => (float) $this->vat_amount, 'total' => (float) $this->total,
            'notes' => $this->notes,
            'items' => SalesOrderItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
