<?php

namespace App\Repositories\Contracts;

use App\Models\Company;
use Illuminate\Support\Collection;

interface CompanySettingRepositoryInterface
{
    public function allFor(Company $company): Collection;

    public function set(Company $company, string $key, mixed $value): void;

    public function get(Company $company, string $key, mixed $default = null): mixed;
}
