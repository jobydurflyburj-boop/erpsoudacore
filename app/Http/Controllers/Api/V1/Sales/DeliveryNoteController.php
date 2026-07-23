<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreDeliveryNoteRequest;
use App\Http\Resources\DeliveryNoteResource;
use App\Models\DeliveryNote;
use App\Models\SalesOrder;
use App\Repositories\Contracts\DeliveryNoteRepositoryInterface;
use App\Services\DeliveryNoteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DeliveryNoteController extends Controller
{
    public function __construct(
        private readonly DeliveryNoteRepositoryInterface $deliveryNotes,
        private readonly DeliveryNoteService $service,
    ) {}

    public function index(Request $request)
    {
        return DeliveryNoteResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->deliveryNotes->query()->with('customer'))
                ->allowedFilters(['status', 'customer_id'])->allowedSorts(['created_at', 'document_date'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreDeliveryNoteRequest $request)
    {
        $note = $this->service->create($request->user(), $request->validated());
        return $this->ok(new DeliveryNoteResource($note->load(['customer', 'warehouse', 'items.product'])), 201);
    }

    public function show(DeliveryNote $deliveryNote)
    {
        return $this->ok(new DeliveryNoteResource($deliveryNote->load(['customer', 'warehouse', 'items.product', 'creator'])));
    }

    public function fromSalesOrder(Request $request, SalesOrder $salesOrder)
    {
        try {
            $note = $this->service->createFromSalesOrder($request->user(), $salesOrder);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new DeliveryNoteResource($note->load(['customer', 'warehouse', 'items.product'])), 201);
    }

    public function deliver(Request $request, DeliveryNote $deliveryNote)
    {
        try {
            $note = $this->service->deliver($request->user(), $deliveryNote);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new DeliveryNoteResource($note->load(['customer', 'warehouse', 'items.product'])));
    }

    public function destroy(DeliveryNote $deliveryNote)
    {
        $this->deliveryNotes->delete($deliveryNote);
        return response()->json(null, 204);
    }
}
