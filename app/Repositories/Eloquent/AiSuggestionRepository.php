<?php
namespace App\Repositories\Eloquent;
use App\Models\AiSuggestion;
use App\Repositories\Contracts\AiSuggestionRepositoryInterface;
class AiSuggestionRepository extends BaseRepository implements AiSuggestionRepositoryInterface
{
    protected string $modelClass = AiSuggestion::class;
    protected array $allowedFilters = ['status', 'category'];
    protected array $allowedSorts = ['created_at'];
    protected string $defaultSort = '-created_at';
}
