<?php
namespace App\Http\Requests\Accounting;
use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', Rule::unique('chart_of_accounts', 'code')->where('tenant_id', $this->user()->tenant_id)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(ChartOfAccount::TYPES)],
            'parent_id' => ['nullable', 'uuid', Rule::exists('chart_of_accounts', 'id')->where('tenant_id', $this->user()->tenant_id)],
        ];
    }
}
