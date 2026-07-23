<?php
namespace App\Http\Requests\Reports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateScheduledReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'frequency' => ['sometimes', Rule::in(['daily', 'weekly', 'monthly'])],
            'format' => ['sometimes', Rule::in(['csv', 'pdf'])],
            'recipients' => ['sometimes', 'array', 'min:1'],
            'recipients.*' => ['email'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
