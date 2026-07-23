<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class ScheduledReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'custom_report_id' => $this->custom_report_id,
            'custom_report_name' => $this->whenLoaded('customReport', fn () => $this->customReport?->name),
            'frequency' => $this->frequency, 'format' => $this->format, 'recipients' => $this->recipients,
            'is_active' => $this->is_active, 'next_run_at' => $this->next_run_at?->toIso8601String(),
            'last_run_at' => $this->last_run_at?->toIso8601String(),
        ];
    }
}
