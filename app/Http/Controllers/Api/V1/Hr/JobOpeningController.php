<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreJobOpeningRequest;
use App\Http\Resources\JobOpeningResource;
use App\Models\JobOpening;
use App\Repositories\Contracts\JobOpeningRepositoryInterface;
use Illuminate\Http\Request;

class JobOpeningController extends Controller
{
    public function __construct(private readonly JobOpeningRepositoryInterface $jobOpenings) {}

    public function index(Request $request)
    {
        $paginated = $this->jobOpenings->paginate($request);
        $paginated->getCollection()->loadCount('applications');
        return JobOpeningResource::collection($paginated);
    }

    public function store(StoreJobOpeningRequest $request)
    {
        $opening = $this->jobOpenings->create(array_merge($request->validated(), [
            'tenant_id' => $request->user()->tenant_id,
            'created_by_user_id' => $request->user()->id,
            'posted_date' => $request->validated()['posted_date'] ?? now()->toDateString(),
        ]));
        return $this->ok(new JobOpeningResource($opening), 201);
    }

    public function close(JobOpening $jobOpening)
    {
        return $this->ok(new JobOpeningResource($this->jobOpenings->update($jobOpening, ['status' => JobOpening::STATUS_CLOSED])));
    }
}
