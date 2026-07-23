<?php
namespace App\Http\Requests\Ai;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AskAiRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'message' => ['required', 'string', 'max:2000'],
            'conversation_id' => ['nullable', 'uuid', Rule::exists('ai_conversations', 'id')->where('user_id', $this->user()->id)],
        ];
    }
}
