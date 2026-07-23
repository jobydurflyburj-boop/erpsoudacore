<?php

namespace App\Http\Requests\Crm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadSourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_en' => [
                'sometimes', 'string', 'max:255',
                Rule::unique('lead_sources', 'name_en')
                    ->where('tenant_id', $this->user()->tenant_id)
                    ->ignore($this->route('leadSource')?->id),
            ],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
