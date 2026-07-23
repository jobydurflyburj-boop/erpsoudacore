<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class AttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->full_name),
            'date' => $this->date?->toDateString(),
            'check_in' => $this->check_in?->toIso8601String(), 'check_out' => $this->check_out?->toIso8601String(),
            'status' => $this->status, 'hours_worked' => $this->hours_worked !== null ? (float) $this->hours_worked : null,
        ];
    }
}
