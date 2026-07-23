<?php

namespace App\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

interface RepositoryInterface
{
    public function find(string $id): ?Model;

    public function findOrFail(string $id): Model;

    public function create(array $attributes): Model;

    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): bool;

    /**
     * List with filtering/sorting/searching/pagination applied from the
     * request — see App\Support\Query\QueryBuilderFactory. Every
     * repository's paginate() goes through the same allow-listed
     * filter/sort/search fields per model, defined in that model's
     * repository via allowedFilters()/allowedSorts()/searchableFields().
     */
    public function paginate(Request $request): LengthAwarePaginator;

    public function query(): Builder;
}
