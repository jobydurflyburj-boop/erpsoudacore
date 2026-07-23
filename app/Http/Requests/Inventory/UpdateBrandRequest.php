<?php
namespace App\Http\Requests\Inventory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBrandRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('brands', 'name')->where('tenant_id', $this->user()->tenant_id)->ignore($this->route('brand')?->id)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
