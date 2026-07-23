<?php

namespace App\Repositories\Contracts;

use Illuminate\Support\Collection;

interface PermissionRepositoryInterface extends RepositoryInterface
{
    public function allGroupedByModule(): Collection;

    public function findManyByNames(array $names): Collection;
}
