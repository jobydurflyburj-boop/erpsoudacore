<?php
namespace App\Http\Controllers\Api\V1\Ai;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiActivityLogResource;
use App\Repositories\Contracts\AiActivityLogRepositoryInterface;
use Illuminate\Http\Request;

class AiActivityLogController extends Controller
{
    public function __construct(private readonly AiActivityLogRepositoryInterface $logs) {}

    public function index(Request $request)
    {
        $paginated = $this->logs->paginate($request);
        $paginated->getCollection()->load('user');
        return AiActivityLogResource::collection($paginated);
    }
}
