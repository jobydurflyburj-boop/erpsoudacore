<?php
namespace App\Repositories\Eloquent;
use App\Models\PayrollRun;
use App\Repositories\Contracts\PayrollRunRepositoryInterface;
class PayrollRunRepository extends BaseRepository implements PayrollRunRepositoryInterface
{
    protected string $modelClass = PayrollRun::class;
    protected array $allowedFilters = ['status', 'period_year', 'period_month'];
    protected array $allowedSorts = ['created_at', 'period_year', 'period_month'];
}
