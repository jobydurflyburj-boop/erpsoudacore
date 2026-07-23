<?php
namespace App\Repositories\Eloquent;
use App\Models\Candidate;
use App\Repositories\Contracts\CandidateRepositoryInterface;
class CandidateRepository extends BaseRepository implements CandidateRepositoryInterface
{
    protected string $modelClass = Candidate::class;
    protected array $allowedSorts = ['created_at', 'full_name'];
    protected array $searchableFields = ['full_name', 'email', 'phone'];
}
