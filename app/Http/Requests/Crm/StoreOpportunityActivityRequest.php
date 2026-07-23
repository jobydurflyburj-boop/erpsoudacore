<?php

namespace App\Http\Requests\Crm;

use App\Models\OpportunityActivity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOpportunityActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(OpportunityActivity::MANUAL_TYPES)],
            'description' => ['required', 'string', 'max:2000'],
        ];
    }
}
