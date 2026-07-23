<?php

namespace App\Http\Requests\Crm;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:crm.create' route middleware
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'company_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'arabic_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'country' => ['nullable', 'string', 'size:2'],
            'city' => ['nullable', 'string', 'max:100'],
            'lead_source_id' => ['nullable', 'uuid', Rule::exists('lead_sources', 'id')->where('tenant_id', $tenantId)],
            'lead_status_id' => ['nullable', 'uuid', Rule::exists('lead_statuses', 'id')->where('tenant_id', $tenantId)],
            'assigned_to_user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'expected_revenue' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'priority' => ['nullable', Rule::in(Lead::PRIORITIES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
