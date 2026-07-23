<?php

namespace App\Http\Requests\Crm;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route-level 'permission:crm.edit' + LeadPolicy::update both apply — see controller
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'arabic_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'lead_source_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('lead_sources', 'id')->where('tenant_id', $tenantId)],
            'lead_status_id' => ['sometimes', 'uuid', Rule::exists('lead_statuses', 'id')->where('tenant_id', $tenantId)],
            'expected_revenue' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'probability' => ['sometimes', 'integer', 'between:0,100'],
            'priority' => ['sometimes', Rule::in(Lead::PRIORITIES)],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
