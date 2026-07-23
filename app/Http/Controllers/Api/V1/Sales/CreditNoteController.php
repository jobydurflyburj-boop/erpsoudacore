<?php
namespace App\Http\Controllers\Api\V1\Sales;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sales\StoreCreditNoteRequest;
use App\Http\Resources\CreditNoteResource;
use App\Models\CreditNote;
use App\Repositories\Contracts\CreditNoteRepositoryInterface;
use App\Services\CreditNoteService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreditNoteController extends Controller
{
    public function __construct(
        private readonly CreditNoteRepositoryInterface $creditNotes,
        private readonly CreditNoteService $service,
    ) {}

    public function index(Request $request)
    {
        return CreditNoteResource::collection(
            \Spatie\QueryBuilder\QueryBuilder::for($this->creditNotes->query()->with('customer'))
                ->allowedFilters(['status', 'customer_id', 'sales_invoice_id'])->allowedSorts(['created_at', 'total'])->defaultSort('-created_at')
                ->paginate((int) $request->integer('page_size', 25))
        );
    }

    public function store(StoreCreditNoteRequest $request)
    {
        try {
            $creditNote = $this->service->create($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['items' => $e->getMessage()]);
        }
        return $this->ok(new CreditNoteResource($creditNote->load(['customer', 'items.product'])), 201);
    }

    public function show(CreditNote $creditNote)
    {
        return $this->ok(new CreditNoteResource($creditNote->load(['customer', 'items.product', 'creator'])));
    }

    public function issue(Request $request, CreditNote $creditNote)
    {
        try {
            $creditNote = $this->service->issue($request->user(), $creditNote);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new CreditNoteResource($creditNote->load(['customer', 'items.product'])));
    }
}
