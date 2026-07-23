<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'department_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'designation_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('designations', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('shifts', 'id')->where('tenant_id', $tenantId)],
            'basic_salary' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
