<?php

namespace App\Http\Requests\Crm;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'customer_id' => ['sometimes', 'uuid', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'stage_id' => ['sometimes', 'uuid', Rule::exists('opportunity_stages', 'id')->where('tenant_id', $tenantId)],
            'amount' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'probability' => ['sometimes', 'integer', 'between:0,100'],
            'expected_close_date' => ['sometimes', 'nullable', 'date'],
            'priority' => ['sometimes', Rule::in(Opportunity::PRIORITIES)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
