<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseRepository implements RepositoryInterface
{
    protected string $modelClass;

    /** @var string[] Filterable columns/relations — allow-listed per repository, never open-ended. */
    protected array $allowedFilters = [];

    /** @var string[] Sortable columns — allow-listed per repository. */
    protected array $allowedSorts = ['created_at'];

    /** @var string[] Columns matched by the `search` query param (OR'd together). */
    protected array $searchableFields = [];

    protected string $defaultSort = '-created_at';

    public function query(): Builder
    {
        return app($this->modelClass)->newQuery();
    }

    public function find(string $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(string $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function paginate(Request $request): LengthAwarePaginator
    {
        $builder = QueryBuilder::for($this->modelClass)
            ->allowedFilters($this->allowedFilters)
            ->allowedSorts($this->allowedSorts)
            ->defaultSort($this->defaultSort);

        if (! empty($this->searchableFields) && $request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $builder->where(function (Builder $query) use ($term) {
                foreach ($this->searchableFields as $field) {
                    $query->orWhere($field, 'ILIKE', $term);
                }
            });
        }

        return $builder->paginate(
            (int) $request->integer('page_size', 25),
            ['*'],
            'page',
            (int) $request->integer('page', 1)
        )->appends($request->query());
    }
}
