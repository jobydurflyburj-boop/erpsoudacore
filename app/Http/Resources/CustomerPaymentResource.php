<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'payment_number' => $this->payment_number,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'amount' => (float) $this->amount, 'allocated_amount' => (float) $this->allocated_amount,
            'unallocated_amount' => $this->unallocatedAmount(),
            'payment_method' => $this->payment_method, 'reference' => $this->reference,
            'payment_date' => $this->payment_date?->toDateString(), 'notes' => $this->notes,
            'allocations' => PaymentAllocationResource::collection($this->whenLoaded('allocations')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
