<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class EmployeeSalaryComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'salary_component_id' => $this->salary_component_id,
            'name_en' => $this->whenLoaded('salaryComponent', fn () => $this->salaryComponent->name_en),
            'type' => $this->whenLoaded('salaryComponent', fn () => $this->salaryComponent->type),
            'amount' => (float) $this->amount,
        ];
    }
}
