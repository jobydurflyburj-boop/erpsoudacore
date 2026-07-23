<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'department_id' => ['sometimes', 'nullable', 'uuid'],
            'default_branch_id' => ['sometimes', 'nullable', 'uuid'],
            'preferred_locale' => ['sometimes', 'in:ar,en'],
            'timezone' => ['sometimes', 'string', 'max:64'],
        ];
    }
}
