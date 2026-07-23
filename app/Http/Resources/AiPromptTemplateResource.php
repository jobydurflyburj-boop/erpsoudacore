<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class AiPromptTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'key' => $this->key, 'content' => $this->content,
            'is_active' => $this->is_active, 'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
