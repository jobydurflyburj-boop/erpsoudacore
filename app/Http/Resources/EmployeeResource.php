<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'employee_number' => $this->employee_number, 'user_id' => $this->user_id,
            'full_name' => $this->full_name, 'email' => $this->email, 'phone' => $this->phone,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'designation' => new DesignationResource($this->whenLoaded('designation')),
            'shift' => new ShiftResource($this->whenLoaded('shift')),
            'hire_date' => $this->hire_date?->toDateString(), 'termination_date' => $this->termination_date?->toDateString(),
            'employment_status' => $this->employment_status, 'basic_salary' => (float) $this->basic_salary,
            'salary_components' => EmployeeSalaryComponentResource::collection($this->whenLoaded('salaryComponents')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
