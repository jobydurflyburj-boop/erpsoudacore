<?php

namespace App\Http\Requests\Crm;

use App\Models\LeadActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLeadActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(LeadActivity::MANUAL_TYPES)],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
