<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PerformanceReviewCycleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'period_start' => $this->period_start?->toDateString(),
            'period_end' => $this->period_end?->toDateString(), 'status' => $this->status,
        ];
    }
}
