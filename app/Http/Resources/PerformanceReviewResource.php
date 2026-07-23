<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PerformanceReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'cycle_id' => $this->cycle_id, 'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->full_name),
            'reviewer_user_id' => $this->reviewer_user_id, 'rating' => $this->rating,
            'strengths' => $this->strengths, 'areas_for_improvement' => $this->areas_for_improvement,
            'status' => $this->status, 'submitted_at' => $this->submitted_at?->toIso8601String(),
        ];
    }
}
