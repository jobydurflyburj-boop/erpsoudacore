<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsIssueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'document_number' => $this->document_number, 'status' => $this->status,
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'document_date' => $this->document_date?->toDateString(),
            'issued_to' => $this->issued_to, 'reason' => $this->reason,
            'items' => GoodsIssueItemResource::collection($this->whenLoaded('items')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
