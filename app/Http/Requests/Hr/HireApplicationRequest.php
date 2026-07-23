<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class HireApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'department_id' => ['nullable', 'uuid', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'designation_id' => ['nullable', 'uuid', Rule::exists('designations', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['nullable', 'uuid', Rule::exists('shifts', 'id')->where('tenant_id', $tenantId)],
            'hire_date' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
        ];
    }
}
