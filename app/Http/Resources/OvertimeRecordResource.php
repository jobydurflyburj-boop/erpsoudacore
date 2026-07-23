<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class OvertimeRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->full_name),
            'date' => $this->date?->toDateString(), 'hours' => (float) $this->hours,
            'rate_multiplier' => (float) $this->rate_multiplier, 'amount' => (float) $this->amount, 'status' => $this->status,
        ];
    }
}
