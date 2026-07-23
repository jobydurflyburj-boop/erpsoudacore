<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'department_id' => ['nullable', 'uuid', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'designation_id' => ['nullable', 'uuid', Rule::exists('designations', 'id')->where('tenant_id', $tenantId)],
            'shift_id' => ['nullable', 'uuid', Rule::exists('shifts', 'id')->where('tenant_id', $tenantId)],
            'hire_date' => ['required', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'employment_status' => ['sometimes', Rule::in(['active', 'on_leave', 'terminated', 'resigned'])],
        ];
    }
}
