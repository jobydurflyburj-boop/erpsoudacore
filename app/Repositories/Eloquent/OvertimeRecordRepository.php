<?php
namespace App\Repositories\Eloquent;
use App\Models\OvertimeRecord;
use App\Repositories\Contracts\OvertimeRecordRepositoryInterface;
class OvertimeRecordRepository extends BaseRepository implements OvertimeRecordRepositoryInterface
{
    protected string $modelClass = OvertimeRecord::class;
    protected array $allowedFilters = ['employee_id', 'status'];
    protected array $allowedSorts = ['created_at', 'date'];
}
