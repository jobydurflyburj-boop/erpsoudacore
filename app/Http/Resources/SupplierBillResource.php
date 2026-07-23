<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierBillResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'purchase_order_id' => $this->purchase_order_id, 'goods_receipt_id' => $this->goods_receipt_id,
            'document_date' => $this->document_date?->toDateString(), 'due_date' => $this->due_date?->toDateString(),
            'subtotal' => (float) $this->subtotal, 'vat_amount' => (float) $this->vat_amount, 'total' => (float) $this->total,
            'paid_amount' => (float) $this->paid_amount, 'credited_amount' => (float) $this->credited_amount,
            'balance_due' => $this->balanceDue(), 'notes' => $this->notes,
            'items' => SupplierBillItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
