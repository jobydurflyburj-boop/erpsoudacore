<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class SalaryComponentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name_en' => $this->name_en, 'name_ar' => $this->name_ar,
            'type' => $this->type, 'calculation_type' => $this->calculation_type,
            'default_amount' => (float) $this->default_amount, 'is_taxable' => $this->is_taxable, 'is_active' => $this->is_active,
        ];
    }
}
