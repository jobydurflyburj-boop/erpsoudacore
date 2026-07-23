<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'full_name' => $this->full_name, 'email' => $this->email,
            'phone' => $this->phone, 'resume_notes' => $this->resume_notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
