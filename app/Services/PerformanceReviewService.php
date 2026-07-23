<?php

namespace App\Services;

use App\Models\PerformanceReview;
use App\Models\User;
use App\Repositories\Contracts\PerformanceReviewRepositoryInterface;
use InvalidArgumentException;

/**
 * Basic Performance Reviews: a review cycle (e.g. "Q3 2026"), one
 * review per employee per cycle (enforced by a real unique constraint,
 * not just convention), with a real draft -> submitted -> acknowledged
 * lifecycle rather than a single free-text field.
 */
class PerformanceReviewService
{
    public function __construct(private readonly PerformanceReviewRepositoryInterface $reviews) {}

    public function submit(User $actor, PerformanceReview $review): PerformanceReview
    {
        if ($review->status !== PerformanceReview::STATUS_DRAFT) {
            throw new InvalidArgumentException("Review is already {$review->status}.");
        }
        if (! $review->rating) {
            throw new InvalidArgumentException('A rating is required before a review can be submitted.');
        }

        return $this->reviews->update($review, ['status' => PerformanceReview::STATUS_SUBMITTED, 'submitted_at' => now()]);
    }

    /** The reviewed employee (or HR on their behalf) acknowledging the review was received. */
    public function acknowledge(PerformanceReview $review): PerformanceReview
    {
        if ($review->status !== PerformanceReview::STATUS_SUBMITTED) {
            throw new InvalidArgumentException('Only a submitted review can be acknowledged.');
        }

        return $this->reviews->update($review, ['status' => PerformanceReview::STATUS_ACKNOWLEDGED]);
    }
}
