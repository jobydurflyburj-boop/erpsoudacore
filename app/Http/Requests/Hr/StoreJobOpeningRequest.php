<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreJobOpeningRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'title' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'uuid', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'description' => ['nullable', 'string', 'max:5000'],
            'positions_count' => ['sometimes', 'integer', 'min:1'],
            'posted_date' => ['nullable', 'date'],
        ];
    }
}
