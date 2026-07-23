<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by 'permission:admin.create' route middleware
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'legal_name_ar' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cr_number' => ['nullable', 'string', 'max:30'],
            'vat_number' => ['nullable', 'digits:15'],
            'national_address' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
            'language' => ['nullable', Rule::in(['ar', 'en'])],
            'fiscal_year_start_month' => ['nullable', 'integer', 'between:1,12'],
            'business_type' => ['nullable', 'string', 'max:60'],
        ];
    }
}
