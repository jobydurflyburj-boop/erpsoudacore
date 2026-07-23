<?php
namespace App\Http\Requests\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllocateSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'supplier_bill_id' => ['required', 'uuid', Rule::exists('supplier_bills', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
