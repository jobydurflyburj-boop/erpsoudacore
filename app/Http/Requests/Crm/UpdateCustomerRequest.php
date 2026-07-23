<?php

namespace App\Http\Requests\Crm;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'customer_type' => ['sometimes', Rule::in(Customer::TYPES)],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'arabic_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:30'],
            'country' => ['sometimes', 'nullable', 'string', 'size:2'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'vat_number' => ['sometimes', 'nullable', 'digits:15'],
            'account_manager_user_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('users', 'id')->where('tenant_id', $tenantId)],
            'status' => ['sometimes', Rule::in([Customer::STATUS_ACTIVE, Customer::STATUS_INACTIVE])],
            'credit_limit' => ['sometimes', 'numeric', 'min:0', 'max:99999999999.99'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
