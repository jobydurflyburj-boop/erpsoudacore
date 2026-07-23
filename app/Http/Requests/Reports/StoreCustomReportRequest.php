<?php
namespace App\Http\Requests\Reports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreCustomReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'source' => ['required', 'string', Rule::in(['sales_invoices', 'supplier_bills', 'journal_entries', 'employees', 'products', 'customers', 'leads', 'opportunities'])],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['string'],
            'filters' => ['sometimes', 'array'],
            'filters.*.column' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', 'string'],
            'filters.*.value' => ['required_with:filters'],
            'group_by' => ['nullable', 'string'],
        ];
    }
}
