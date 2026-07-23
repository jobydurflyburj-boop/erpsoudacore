<?php

namespace App\Repositories\Eloquent;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

class TenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    protected string $modelClass = Tenant::class;

    protected array $allowedFilters = ['status', 'default_locale'];

    protected array $allowedSorts = ['created_at', 'name', 'status'];

    protected array $searchableFields = ['name', 'subdomain'];

    public function findBySubdomain(string $subdomain): ?Tenant
    {
        return Tenant::where('subdomain', $subdomain)->first();
    }

    public function subdomainExists(string $subdomain): bool
    {
        return Tenant::where('subdomain', $subdomain)->exists();
    }
}
