<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['sometimes', 'string', 'max:255'],
            'legal_name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trade_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'cr_number' => ['sometimes', 'nullable', 'string', 'max:30'],
            'vat_number' => ['sometimes', 'nullable', 'digits:15'],
            'national_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'timezone' => ['sometimes', 'string', 'max:64'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'language' => ['sometimes', Rule::in(['ar', 'en'])],
            'fiscal_year_start_month' => ['sometimes', 'integer', 'between:1,12'],
            'business_type' => ['sometimes', 'nullable', 'string', 'max:60'],
        ];
    }
}
