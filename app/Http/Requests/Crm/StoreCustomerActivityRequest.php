<?php

namespace App\Http\Requests\Crm;

use App\Models\CustomerActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(CustomerActivity::MANUAL_TYPES)],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
