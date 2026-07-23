<?php

namespace App\Http\Controllers\Api\V1\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\HireApplicationRequest;
use App\Http\Requests\Hr\StoreJobApplicationRequest;
use App\Http\Requests\Hr\UpdateApplicationStatusRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\JobApplicationResource;
use App\Models\JobApplication;
use App\Repositories\Contracts\JobApplicationRepositoryInterface;
use App\Services\RecruitmentService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class JobApplicationController extends Controller
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applications,
        private readonly RecruitmentService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->applications->paginate($request);
        $paginated->getCollection()->load(['jobOpening', 'candidate']);

        return JobApplicationResource::collection($paginated);
    }

    public function store(StoreJobApplicationRequest $request)
    {
        $application = $this->applications->create(array_merge($request->validated(), [
            'tenant_id' => $request->user()->tenant_id,
            'applied_at' => $request->validated()['applied_at'] ?? now()->toDateString(),
        ]));

        return $this->ok(new JobApplicationResource($application->load(['jobOpening', 'candidate'])), 201);
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, JobApplication $jobApplication)
    {
        try {
            $jobApplication = $this->service->updateApplicationStatus($request->user(), $jobApplication, $request->validated()['status']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new JobApplicationResource($jobApplication->load(['jobOpening', 'candidate'])));
    }

    /** Real integration: hiring an application creates the actual Employee record — see RecruitmentService::hire(). */
    public function hire(HireApplicationRequest $request, JobApplication $jobApplication)
    {
        try {
            $employee = $this->service->hire($request->user(), $jobApplication->load('candidate'), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new EmployeeResource($employee), 201);
    }
}
