<?php
namespace App\Http\Requests\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'sku' => ['sometimes', 'string', 'max:60', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)->ignore($this->route('product')?->id)],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:60', Rule::unique('products', 'barcode')->where('tenant_id', $tenantId)->ignore($this->route('product')?->id)],
            'name_en' => ['sometimes', 'string', 'max:255'],
            'name_ar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'category' => ['sometimes', 'nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('product_categories', 'id')->where('tenant_id', $tenantId)],
            'unit' => ['sometimes', 'string', 'max:20'],
            'unit_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('units', 'id')->where('tenant_id', $tenantId)],
            'brand_id' => ['sometimes', 'nullable', 'uuid', Rule::exists('brands', 'id')->where('tenant_id', $tenantId)],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'sale_price' => ['sometimes', 'numeric', 'min:0'],
            'vat_rate' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'reorder_point' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
