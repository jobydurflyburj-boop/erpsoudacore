<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'sales_order_id' => $this->sales_order_id,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'document_date' => $this->document_date?->toDateString(), 'notes' => $this->notes,
            'items' => DeliveryNoteItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
