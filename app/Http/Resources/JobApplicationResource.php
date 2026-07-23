<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class JobApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'job_opening' => new JobOpeningResource($this->whenLoaded('jobOpening')),
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'status' => $this->status, 'applied_at' => $this->applied_at?->toDateString(), 'notes' => $this->notes,
        ];
    }
}
