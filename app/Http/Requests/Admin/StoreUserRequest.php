<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // gated by the 'permission:admin.create' route middleware, not here
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenantId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'role_id' => ['required', 'uuid', Rule::exists('roles', 'id')->where('tenant_id', $tenantId)],
            'department_id' => ['nullable', 'uuid', Rule::exists('departments', 'id')->where('tenant_id', $tenantId)],
            'default_branch_id' => ['nullable', 'uuid', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['uuid', Rule::exists('branches', 'id')->where('tenant_id', $tenantId)],
            'preferred_locale' => ['nullable', 'in:ar,en'],
        ];
    }
}
