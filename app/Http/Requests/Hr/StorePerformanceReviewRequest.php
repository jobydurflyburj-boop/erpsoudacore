<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StorePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'cycle_id' => ['required', 'uuid', Rule::exists('performance_review_cycles', 'id')->where('tenant_id', $tenantId)],
            'employee_id' => ['required', 'uuid', Rule::exists('employees', 'id')->where('tenant_id', $tenantId)],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'strengths' => ['nullable', 'string', 'max:5000'],
            'areas_for_improvement' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
