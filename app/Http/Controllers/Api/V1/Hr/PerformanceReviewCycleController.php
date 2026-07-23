<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StorePerformanceReviewCycleRequest;
use App\Http\Resources\PerformanceReviewCycleResource;
use App\Models\PerformanceReviewCycle;
use App\Repositories\Contracts\PerformanceReviewCycleRepositoryInterface;
use Illuminate\Http\Request;

class PerformanceReviewCycleController extends Controller
{
    public function __construct(private readonly PerformanceReviewCycleRepositoryInterface $cycles) {}

    public function index(Request $request) { return PerformanceReviewCycleResource::collection($this->cycles->paginate($request)); }

    public function store(StorePerformanceReviewCycleRequest $request)
    {
        $cycle = $this->cycles->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new PerformanceReviewCycleResource($cycle), 201);
    }

    public function close(PerformanceReviewCycle $performanceReviewCycle)
    {
        return $this->ok(new PerformanceReviewCycleResource($this->cycles->update($performanceReviewCycle, ['status' => PerformanceReviewCycle::STATUS_CLOSED])));
    }
}
