<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class CustomReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'description' => $this->description,
            'source' => $this->source, 'columns' => $this->columns, 'filters' => $this->filters,
            'group_by' => $this->group_by, 'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
