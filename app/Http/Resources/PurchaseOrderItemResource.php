<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'product' => new ProductResource($this->whenLoaded('product')),
            'description' => $this->description, 'quantity' => (float) $this->quantity,
            'unit_cost' => (float) $this->unit_cost, 'line_total' => (float) $this->line_total,
        ];
    }
}
