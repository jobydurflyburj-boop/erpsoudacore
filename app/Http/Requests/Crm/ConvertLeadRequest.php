<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;

class ConvertLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:crm.edit' route middleware + LeadPolicy::update, checked in the controller
    }

    public function rules(): array
    {
        return [
            // Optional overrides applied on top of the data copied from
            // the lead — e.g. correcting a company name before it
            // becomes permanent on the Customer record.
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0', 'max:99999999999.99'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
        ];
    }
}
