<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockAdjustmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'product' => new ProductResource($this->whenLoaded('product')), 'quantity_change' => (float) $this->quantity_change, 'reason' => $this->reason];
    }
}
