<?php
namespace App\Http\Requests\Ai;
use Illuminate\Foundation\Http\FormRequest;
class ReportSummaryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'report_label' => ['required', 'string', 'max:255'],
            'data' => ['required', 'array'],
        ];
    }
}
