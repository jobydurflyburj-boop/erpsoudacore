<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class PayslipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'payroll_run_id' => $this->payroll_run_id, 'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->full_name),
            'basic_salary' => (float) $this->basic_salary, 'total_allowances' => (float) $this->total_allowances,
            'overtime_amount' => (float) $this->overtime_amount, 'total_deductions' => (float) $this->total_deductions,
            'gross_pay' => (float) $this->gross_pay, 'net_pay' => (float) $this->net_pay, 'status' => $this->status,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'lines' => PayslipLineResource::collection($this->whenLoaded('lines')),
        ];
    }
}
