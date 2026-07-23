<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class AiActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'feature' => $this->feature,
            'user_name' => $this->whenLoaded('user', fn () => $this->user?->full_name),
            'provider' => $this->provider, 'model' => $this->model, 'summary' => $this->summary,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
