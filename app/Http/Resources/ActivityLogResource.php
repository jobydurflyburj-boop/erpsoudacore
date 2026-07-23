<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'module' => $this->module,
            'description' => $this->description,
            'user' => new UserResource($this->whenLoaded('user')),
            'ip_address' => $this->ip_address,
            'browser' => $this->browser,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
