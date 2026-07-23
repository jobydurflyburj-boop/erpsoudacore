<?php
namespace App\Http\Requests\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'name_en' => ['required', 'string', 'max:255', Rule::unique('product_categories', 'name_en')->where('tenant_id', $tenantId)],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'uuid', Rule::exists('product_categories', 'id')->where('tenant_id', $tenantId)],
        ];
    }
}
