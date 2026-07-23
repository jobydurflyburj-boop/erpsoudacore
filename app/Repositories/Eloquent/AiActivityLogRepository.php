<?php
namespace App\Repositories\Eloquent;
use App\Models\AiActivityLog;
use App\Repositories\Contracts\AiActivityLogRepositoryInterface;
class AiActivityLogRepository extends BaseRepository implements AiActivityLogRepositoryInterface
{
    protected string $modelClass = AiActivityLog::class;
    protected array $allowedFilters = ['feature', 'user_id'];
    protected array $allowedSorts = ['created_at'];
    protected string $defaultSort = '-created_at';
}
