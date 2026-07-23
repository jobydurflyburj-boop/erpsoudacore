<?php
namespace App\Repositories\Eloquent;
use App\Models\AiPromptTemplate;
use App\Repositories\Contracts\AiPromptTemplateRepositoryInterface;
class AiPromptTemplateRepository extends BaseRepository implements AiPromptTemplateRepositoryInterface
{
    protected string $modelClass = AiPromptTemplate::class;
    protected array $allowedFilters = ['key', 'is_active'];
    protected array $allowedSorts = ['created_at', 'key'];
}
