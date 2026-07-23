<?php
namespace App\Http\Requests\Ai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateAiSettingsRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'is_enabled' => ['sometimes', 'boolean'],
            'provider_override' => ['sometimes', 'nullable', Rule::in(['anthropic', 'openai'])],
            'insights_enabled' => ['sometimes', 'boolean'],
            'notifications_enabled' => ['sometimes', 'boolean'],
            'automation_suggestions_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
