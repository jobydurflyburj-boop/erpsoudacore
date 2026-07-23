<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreSalesReturnRequest;
use App\Http\Resources\SalesReturnResource;
use App\Models\SalesReturn;
use App\Repositories\Contracts\SalesReturnRepositoryInterface;
use App\Services\SalesReturnService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SalesReturnController extends Controller
{
    public function __construct(
        private readonly SalesReturnRepositoryInterface $returns,
        private readonly SalesReturnService $service,
    ) {}

    public function index(Request $request)
    {
        return SalesReturnResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->returns->query()->with('customer'))
                ->allowedFilters(['status', 'customer_id'])->allowedSorts(['created_at', 'document_date'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreSalesReturnRequest $request)
    {
        $return = $this->service->create($request->user(), $request->validated());
        return $this->ok(new SalesReturnResource($return->load(['customer', 'items.product'])), 201);
    }

    public function show(SalesReturn $salesReturn)
    {
        return $this->ok(new SalesReturnResource($salesReturn->load(['customer', 'items.product', 'creator', 'creditNote'])));
    }

    public function receive(Request $request, SalesReturn $salesReturn)
    {
        try {
            $return = $this->service->receive($request->user(), $salesReturn);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new SalesReturnResource($return->load(['customer', 'items.product', 'creditNote'])));
    }
}
