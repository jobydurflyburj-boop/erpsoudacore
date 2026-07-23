<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'supplier_number' => $this->supplier_number, 'name' => $this->name,
            'email' => $this->email, 'phone' => $this->phone, 'vat_number' => $this->vat_number,
            'payment_terms_days' => $this->payment_terms_days, 'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
