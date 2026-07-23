<?php
namespace App\Http\Requests\Reports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateCustomReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'source' => ['sometimes', 'string', Rule::in(['sales_invoices', 'supplier_bills', 'journal_entries', 'employees', 'products', 'customers', 'leads', 'opportunities'])],
            'columns' => ['sometimes', 'array', 'min:1'],
            'columns.*' => ['string'],
            'filters' => ['sometimes', 'array'],
            'filters.*.column' => ['required_with:filters', 'string'],
            'filters.*.operator' => ['required_with:filters', 'string'],
            'filters.*.value' => ['required_with:filters'],
            'group_by' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
