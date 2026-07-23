<?php
namespace App\Http\Requests\Ai;
use Illuminate\Foundation\Http\FormRequest;
class UpsertAiPromptTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'key' => ['required', 'string', 'max:60'],
            'content' => ['required', 'string', 'max:4000'],
        ];
    }
}
