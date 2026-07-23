<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'goods_receipt_id' => $this->goods_receipt_id, 'debit_note_id' => $this->debit_note_id,
            'document_date' => $this->document_date?->toDateString(), 'reason' => $this->reason,
            'items' => PurchaseReturnItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
