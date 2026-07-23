<?php

namespace App\Http\Requests\Crm;

use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:crm.create' route middleware
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'customer_id' => ['required', 'uuid', Rule::exists('customers', 'id')->where('tenant_id', $tenantId)],
            'lead_id' => ['nullable', 'uuid', Rule::exists('leads', 'id')->where('tenant_id', $tenantId)],
            'stage_id' => ['nullable', 'uuid', Rule::exists('opportunity_stages', 'id')->where('tenant_id', $tenantId)],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:99999999999.99'],
            'probability' => ['nullable', 'integer', 'between:0,100'],
            'expected_close_date' => ['nullable', 'date'],
            'assigned_to_user_id' => ['nullable', 'uuid', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'priority' => ['nullable', Rule::in(Opportunity::PRIORITIES)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
