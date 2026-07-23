<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class AiSettingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'is_enabled' => $this->is_enabled, 'provider_override' => $this->provider_override,
            'insights_enabled' => $this->insights_enabled, 'notifications_enabled' => $this->notifications_enabled,
            'automation_suggestions_enabled' => $this->automation_suggestions_enabled,
        ];
    }
}
