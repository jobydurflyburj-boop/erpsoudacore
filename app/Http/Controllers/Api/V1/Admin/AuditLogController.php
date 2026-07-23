<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = QueryBuilder::for(AuditLog::class)
            ->allowedFilters(['auditable_type', 'action', 'user_id'])
            ->allowedSorts(['created_at'])
            ->defaultSort('-created_at')
            ->paginate((int) $request->integer('page_size', 25));

        return $this->ok($logs);
    }
}
