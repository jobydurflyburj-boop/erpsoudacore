<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class AssignSalaryComponentsRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'components' => ['required', 'array'],
            'components.*.salary_component_id' => ['required', 'uuid', Rule::exists('salary_components', 'id')->where('tenant_id', $tenantId)],
            'components.*.amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
