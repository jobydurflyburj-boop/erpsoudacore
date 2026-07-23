<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'sales_order_id' => $this->sales_order_id,
            'document_date' => $this->document_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'subtotal' => (float) $this->subtotal, 'vat_amount' => (float) $this->vat_amount, 'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount, 'credited_amount' => (float) $this->credited_amount,
            'balance_due' => $this->balanceDue(),
            'notes' => $this->notes,
            'items' => SalesInvoiceItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
