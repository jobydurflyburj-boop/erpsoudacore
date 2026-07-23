<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'sku' => $this->sku, 'barcode' => $this->barcode,
            'name_en' => $this->name_en, 'name_ar' => $this->name_ar,
            'category' => $this->category, 'category_ref' => new ProductCategoryResource($this->whenLoaded('categoryRef')),
            'unit' => $this->unit, 'unit_ref' => new UnitResource($this->whenLoaded('unitRef')),
            'brand' => new BrandResource($this->whenLoaded('brand')),
            'cost_price' => (float) $this->cost_price, 'sale_price' => (float) $this->sale_price, 'vat_rate' => (float) $this->vat_rate,
            'reorder_point' => (float) $this->reorder_point,
            'total_stock' => $this->when($this->relationLoaded('stockLevels'), fn () => $this->totalStock()),
            'is_low_stock' => $this->when($this->relationLoaded('stockLevels'), fn () => $this->isLowStock()),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
