<?php
namespace App\Http\Requests\Sales;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AllocatePaymentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'sales_invoice_id' => ['required', 'uuid', Rule::exists('sales_invoices', 'id')->where('tenant_id', $this->user()->tenant_id)],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
