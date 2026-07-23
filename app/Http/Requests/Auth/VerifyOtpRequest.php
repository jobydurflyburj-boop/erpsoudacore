<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Opaque server-issued ticket — NEVER a raw user_id. See
            // OtpService::generateWithTicket / verifyByTicket and
            // docs/FOUNDATION.md "Tenant isolation review — OTP ticket".
            'ticket' => ['required', 'string', 'size:48'],
            'code' => ['required', 'string', 'size:6'],
            'remember_me' => ['sometimes', 'boolean'],
        ];
    }
}
