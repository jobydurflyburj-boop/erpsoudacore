<?php

namespace App\Http\Requests\Auth;

use App\Services\PasswordPolicyService;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', app(PasswordPolicyService::class)->rule()],
        ];
    }
}
