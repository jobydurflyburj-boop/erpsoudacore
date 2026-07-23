<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PayrollRunResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'run_number' => $this->run_number,
            'period_month' => $this->period_month, 'period_year' => $this->period_year, 'status' => $this->status,
            'total_gross' => (float) $this->total_gross, 'total_deductions' => (float) $this->total_deductions,
            'total_net' => (float) $this->total_net, 'processed_at' => $this->processed_at?->toIso8601String(),
            'payslips' => PayslipResource::collection($this->whenLoaded('payslips')),
        ];
    }
}
