<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface TenantRepositoryInterface extends RepositoryInterface
{
    public function findBySubdomain(string $subdomain): ?Tenant;

    public function subdomainExists(string $subdomain): bool;
}
