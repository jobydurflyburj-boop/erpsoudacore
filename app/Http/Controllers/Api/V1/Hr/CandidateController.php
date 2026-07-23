<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Repositories\Contracts\CandidateRepositoryInterface;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function __construct(private readonly CandidateRepositoryInterface $candidates) {}

    public function index(Request $request) { return CandidateResource::collection($this->candidates->paginate($request)); }

    public function store(StoreCandidateRequest $request)
    {
        $candidate = $this->candidates->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new CandidateResource($candidate), 201);
    }
}
