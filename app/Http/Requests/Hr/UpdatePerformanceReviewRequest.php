<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
class UpdatePerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'strengths' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'areas_for_improvement' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
