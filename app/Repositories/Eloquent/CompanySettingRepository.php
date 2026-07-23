<?php

namespace App\Repositories\Eloquent;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Repositories\Contracts\CompanySettingRepositoryInterface;
use Illuminate\Support\Collection;

class CompanySettingRepository implements CompanySettingRepositoryInterface
{
    public function allFor(Company $company): Collection
    {
        return CompanySetting::where('company_id', $company->id)->get()->pluck('value', 'key');
    }

    public function set(Company $company, string $key, mixed $value): void
    {
        CompanySetting::updateOrCreate(
            ['company_id' => $company->id, 'key' => $key],
            ['tenant_id' => $company->tenant_id, 'value' => $value]
        );
    }

    public function get(Company $company, string $key, mixed $default = null): mixed
    {
        return CompanySetting::where('company_id', $company->id)->where('key', $key)->value('value') ?? $default;
    }
}
