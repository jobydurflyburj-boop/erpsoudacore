<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name_en' => $this->name_en, 'name_ar' => $this->name_ar, 'parent_id' => $this->parent_id, 'is_active' => $this->is_active];
    }
}
