<?php
namespace App\Http\Requests\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        return [
            'sku' => ['required', 'string', 'max:60', Rule::unique('products', 'sku')->where('tenant_id', $tenantId)],
            'barcode' => ['nullable', 'string', 'max:60', Rule::unique('products', 'barcode')->where('tenant_id', $tenantId)],
            'name_en' => ['required', 'string', 'max:255'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'uuid', Rule::exists('product_categories', 'id')->where('tenant_id', $tenantId)],
            'unit' => ['nullable', 'string', 'max:20'],
            'unit_id' => ['nullable', 'uuid', Rule::exists('units', 'id')->where('tenant_id', $tenantId)],
            'brand_id' => ['nullable', 'uuid', Rule::exists('brands', 'id')->where('tenant_id', $tenantId)],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
