<?php
namespace App\Http\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'account' => new ChartOfAccountResource($this->whenLoaded('account')),
            'debit' => (float) $this->debit, 'credit' => (float) $this->credit,
        ];
    }
}
