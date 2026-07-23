<?php
namespace App\Http\Controllers\Api\V1\Purchase;
use App\Http\Controllers\Controller;
use App\Http\Requests\Purchase\StoreDebitNoteRequest;
use App\Http\Resources\DebitNoteResource;
use App\Models\DebitNote;
use App\Repositories\Contracts\DebitNoteRepositoryInterface;
use App\Services\DebitNoteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DebitNoteController extends Controller
{
    public function __construct(
        private readonly DebitNoteRepositoryInterface $debitNotes,
        private readonly DebitNoteService $service,
    ) {}

    public function index(Request $request)
    {
        return DebitNoteResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->debitNotes->query()->with('supplier'))
                ->allowedFilters(['status', 'supplier_id', 'supplier_bill_id'])->allowedSorts(['created_at', 'total'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreDebitNoteRequest $request)
    {
        try {
            $debitNote = $this->service->create($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }
        return $this->ok(new DebitNoteResource($debitNote->load(['supplier', 'items.product'])), 201);
    }

    public function show(DebitNote $debitNote)
    {
        return $this->ok(new DebitNoteResource($debitNote->load(['supplier', 'items.product', 'creator'])));
    }

    public function issue(Request $request, DebitNote $debitNote)
    {
        try {
            $debitNote = $this->service->issue($request->user(), $debitNote);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new DebitNoteResource($debitNote->load(['supplier', 'items.product'])));
    }
}
