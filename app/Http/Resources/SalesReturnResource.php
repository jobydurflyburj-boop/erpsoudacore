<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'sales_invoice_id' => $this->sales_invoice_id, 'credit_note_id' => $this->credit_note_id,
            'document_date' => $this->document_date?->toDateString(), 'reason' => $this->reason,
            'items' => SalesReturnItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
