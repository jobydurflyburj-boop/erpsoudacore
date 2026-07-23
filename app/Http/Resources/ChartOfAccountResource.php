<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartOfAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'name_en' => $this->name_en, 'name_ar' => $this->name_ar,
            'type' => $this->type, 'parent_id' => $this->parent_id, 'is_active' => $this->is_active,
        ];
    }
}
