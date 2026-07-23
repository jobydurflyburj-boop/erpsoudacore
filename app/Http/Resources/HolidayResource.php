<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class HolidayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'name' => $this->name, 'date' => $this->date?->toDateString(),
            'is_recurring_annually' => $this->is_recurring_annually,
        ];
    }
}
