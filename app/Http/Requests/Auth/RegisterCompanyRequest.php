<?php

namespace App\Http\Requests\Auth;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class RegisterCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint
    }

    public function rules(): array
    {
        return [
            'legal_name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'subdomain' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-z0-9-]+$/', 'unique:tenants,subdomain'],
            'cr_number' => ['nullable', 'string', 'max:30'],
            'vat_number' => ['nullable', 'digits:15'],
            'admin_full_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => ['required', app(PasswordPolicyService::class)->rule()],
            'default_locale' => ['nullable', 'in:ar,en'],
        ];
    }
}
