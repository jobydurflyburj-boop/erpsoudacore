<?php
namespace App\Repositories\Eloquent;
use App\Models\Designation;
use App\Repositories\Contracts\DesignationRepositoryInterface;
class DesignationRepository extends BaseRepository implements DesignationRepositoryInterface
{
    protected string $modelClass = Designation::class;
    protected array $allowedFilters = ['is_active', 'department_id'];
    protected array $allowedSorts = ['created_at', 'title_en'];
    protected array $searchableFields = ['title_en', 'title_ar'];
}
