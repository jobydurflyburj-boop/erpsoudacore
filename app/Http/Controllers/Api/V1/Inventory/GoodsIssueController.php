<?php
namespace App\Http\Controllers\Api\V1\Inventory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreGoodsIssueRequest;
use App\Http\Resources\GoodsIssueResource;
use App\Models\GoodsIssue;
use App\Repositories\Contracts\GoodsIssueRepositoryInterface;
use App\Services\GoodsIssueService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class GoodsIssueController extends Controller
{
    public function __construct(
        private readonly GoodsIssueRepositoryInterface $issues,
        private readonly GoodsIssueService $service,
    ) {}

    public function index(Request $request)
    {
        return GoodsIssueResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->issues->query()->with('warehouse'))
                ->allowedFilters(['status', 'warehouse_id'])->allowedSorts(['created_at'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreGoodsIssueRequest $request)
    {
        $issue = $this->service->create($request->user(), $request->validated());
        return $this->ok(new GoodsIssueResource($issue->load(['warehouse', 'items.product'])), 201);
    }

    public function show(GoodsIssue $goodsIssue)
    {
        return $this->ok(new GoodsIssueResource($goodsIssue->load(['warehouse', 'items.product', 'creator'])));
    }

    public function issue(Request $request, GoodsIssue $goodsIssue)
    {
        try {
            $issue = $this->service->issue($request->user(), $goodsIssue);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new GoodsIssueResource($issue->load(['warehouse', 'items.product'])));
    }

    public function destroy(GoodsIssue $goodsIssue)
    {
        $this->issues->delete($goodsIssue);
        return response()->json(null, 204);
    }
}
