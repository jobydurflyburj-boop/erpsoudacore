<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'entry_number' => $this->entry_number,
            'entry_date' => $this->entry_date?->toDateString(), 'memo' => $this->memo,
            'source_type' => $this->source_type, 'source_id' => $this->source_id,
            'is_reversed' => $this->is_reversed, 'reversed_by_entry_id' => $this->reversed_by_entry_id,
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
