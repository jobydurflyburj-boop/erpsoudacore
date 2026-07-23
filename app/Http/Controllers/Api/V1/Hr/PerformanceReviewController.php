<?php

namespace App\Http\Controllers\Api\V1\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StorePerformanceReviewRequest;
use App\Http\Requests\Hr\UpdatePerformanceReviewRequest;
use App\Http\Resources\PerformanceReviewResource;
use App\Models\PerformanceReview;
use App\Repositories\Contracts\PerformanceReviewRepositoryInterface;
use App\Services\PerformanceReviewService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PerformanceReviewController extends Controller
{
    public function __construct(
        private readonly PerformanceReviewRepositoryInterface $reviews,
        private readonly PerformanceReviewService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->reviews->paginate($request);
        $paginated->getCollection()->load('employee');

        return PerformanceReviewResource::collection($paginated);
    }

    public function store(StorePerformanceReviewRequest $request)
    {
        $review = $this->reviews->create(array_merge($request->validated(), [
            'tenant_id' => $request->user()->tenant_id,
            'reviewer_user_id' => $request->user()->id,
            'status' => PerformanceReview::STATUS_DRAFT,
        ]));

        return $this->ok(new PerformanceReviewResource($review->load('employee')), 201);
    }

    public function update(UpdatePerformanceReviewRequest $request, PerformanceReview $performanceReview)
    {
        return $this->ok(new PerformanceReviewResource($this->reviews->update($performanceReview, $request->validated())));
    }

    public function submit(Request $request, PerformanceReview $performanceReview)
    {
        try {
            $performanceReview = $this->service->submit($request->user(), $performanceReview);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new PerformanceReviewResource($performanceReview));
    }

    public function acknowledge(PerformanceReview $performanceReview)
    {
        try {
            $performanceReview = $this->service->acknowledge($performanceReview);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new PerformanceReviewResource($performanceReview));
    }
}
