<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'job_opening_id' => ['required', 'uuid', Rule::exists('job_openings', 'id')->where('tenant_id', $tenantId)],
            'candidate_id' => ['required', 'uuid', Rule::exists('candidates', 'id')->where('tenant_id', $tenantId)],
            'applied_at' => ['sometimes', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
