<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'product' => new ProductResource($this->whenLoaded('product')), 'quantity' => (float) $this->quantity, 'unit_cost' => (float) $this->unit_cost];
    }
}
