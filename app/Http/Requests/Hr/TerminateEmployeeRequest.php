<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
class TerminateEmployeeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return ['termination_date' => ['required', 'date']];
    }
}
