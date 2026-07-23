<?php
namespace App\Http\Requests\Reports;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreScheduledReportRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'name' => ['required', 'string', 'max:255'],
            'custom_report_id' => ['required', 'uuid', Rule::exists('custom_reports', 'id')->where('tenant_id', $tenantId)],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly'])],
            'format' => ['sometimes', Rule::in(['csv', 'pdf'])],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*' => ['email'],
        ];
    }
}
