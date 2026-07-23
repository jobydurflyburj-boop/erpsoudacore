<?php
namespace App\Http\Controllers\Api\V1\Accounting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreJournalEntryRequest;
use App\Http\Resources\JournalEntryResource;
use App\Models\JournalEntry;
use App\Repositories\Contracts\JournalEntryRepositoryInterface;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class JournalEntryController extends Controller
{
    public function __construct(
        private readonly JournalEntryRepositoryInterface $entries,
        private readonly AccountingService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->entries->paginate($request);
        $paginated->getCollection()->load('lines.account');
        return JournalEntryResource::collection($paginated);
    }

    public function store(StoreJournalEntryRequest $request)
    {
        try {
            $entry = $this->service->createEntry($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['lines' => $e->getMessage()]);
        }
        return $this->ok(new JournalEntryResource($entry->load('lines.account')), 201);
    }

    public function show(JournalEntry $journalEntry)
    {
        return $this->ok(new JournalEntryResource($journalEntry->load(['lines.account', 'creator'])));
    }

    /** Real journal entry reversal — see AccountingService::reverseEntry() for why this creates a new entry rather than editing the original. */
    public function reverse(Request $request, JournalEntry $journalEntry)
    {
        try {
            $reversal = $this->service->reverseEntry($request->user(), $journalEntry);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new JournalEntryResource($reversal->load(['lines.account', 'creator'])), 201);
    }
}
