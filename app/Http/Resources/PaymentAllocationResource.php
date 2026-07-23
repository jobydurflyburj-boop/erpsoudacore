<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentAllocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'sales_invoice_id' => $this->sales_invoice_id, 'amount' => (float) $this->amount, 'created_at' => $this->created_at?->toIso8601String()];
    }
}
