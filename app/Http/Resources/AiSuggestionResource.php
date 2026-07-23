<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class AiSuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'category' => $this->category, 'title' => $this->title,
            'description' => $this->description, 'status' => $this->status,
            'created_at' => $this->created_at?->toIso8601String(), 'dismissed_at' => $this->dismissed_at?->toIso8601String(),
        ];
    }
}
