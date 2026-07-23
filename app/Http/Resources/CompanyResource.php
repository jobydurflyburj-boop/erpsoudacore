<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legal_name,
            'legal_name_ar' => $this->legal_name_ar,
            'trade_name' => $this->trade_name,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
            'cr_number' => $this->cr_number,
            'vat_number' => $this->vat_number,
            'national_address' => $this->national_address,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'timezone' => $this->timezone,
            'currency' => $this->currency,
            'language' => $this->language,
            'fiscal_year_start_month' => $this->fiscal_year_start_month,
            'business_type' => $this->business_type,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
