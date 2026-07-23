<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryNoteItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'product' => new ProductResource($this->whenLoaded('product')), 'description' => $this->description, 'quantity' => (float) $this->quantity];
    }
}
