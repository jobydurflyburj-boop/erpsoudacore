<?php

namespace App\Services;

use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use App\Models\OpportunityStage;
use App\Models\User;
use App\Repositories\Contracts\OpportunityRepositoryInterface;
use App\Repositories\Contracts\OpportunityStageRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OpportunityService
{
    public function __construct(
        private readonly OpportunityRepositoryInterface $opportunities,
        private readonly OpportunityStageRepositoryInterface $stages,
        private readonly SequenceService $sequences,
        private readonly NotificationService $notifications,
    ) {}

    public function create(User $creator, array $data): Opportunity
    {
        return DB::transaction(function () use ($creator, $data) {
            $data['stage_id'] ??= $this->stages->defaultFor($creator->tenant)?->id;

            if (empty($data['stage_id'])) {
                throw new InvalidArgumentException('No default opportunity stage is configured for this company — specify stage_id explicitly or set one as default.');
            }

            $stage = OpportunityStage::find($data['stage_id']);
            $data['probability'] ??= $stage?->default_probability ?? 0;

            $opportunity = $this->opportunities->create(array_merge($data, [
                'tenant_id' => $creator->tenant_id,
                'opportunity_number' => $this->sequences->next($creator->tenant_id, 'opportunity_number', 'OP'),
                'created_by_user_id' => $creator->id,
                'updated_by_user_id' => $creator->id,
                'priority' => $data['priority'] ?? Opportunity::PRIORITY_NORMAL,
            ]));

            $this->logActivity($opportunity, $creator, OpportunityActivity::TYPE_CREATED, "Opportunity {$opportunity->opportunity_number} created.");

            if (! empty($data['assigned_to_user_id']) && $data['assigned_to_user_id'] !== $creator->id) {
                $this->notifyAssignment($opportunity, $data['assigned_to_user_id']);
            }

            return $opportunity;
        });
    }

    public function update(User $actor, Opportunity $opportunity, array $data): Opportunity
    {
        return DB::transaction(function () use ($actor, $opportunity, $data) {
            $originalStageId = $opportunity->stage_id;

            $opportunity = $this->opportunities->update($opportunity, array_merge($data, ['updated_by_user_id' => $actor->id]));

            if (array_key_exists('stage_id', $data) && $data['stage_id'] !== $originalStageId) {
                $this->handleStageChange($actor, $opportunity, $originalStageId, $data['stage_id']);
            }

            return $opportunity;
        });
    }

    public function assign(User $actor, Opportunity $opportunity, string $assignedToUserId): Opportunity
    {
        return DB::transaction(function () use ($actor, $opportunity, $assignedToUserId) {
            $previousAssigneeId = $opportunity->assigned_to_user_id;

            if ($previousAssigneeId === $assignedToUserId) {
                return $opportunity;
            }

            $opportunity = $this->opportunities->update($opportunity, [
                'assigned_to_user_id' => $assignedToUserId,
                'updated_by_user_id' => $actor->id,
            ]);

            $assignee = User::findOrFail($assignedToUserId);

            $this->logActivity(
                $opportunity, $actor, OpportunityActivity::TYPE_ASSIGNED,
                "Assigned to {$assignee->full_name}.",
                ['from_user_id' => $previousAssigneeId, 'to_user_id' => $assignedToUserId]
            );

            if ($assignedToUserId !== $actor->id) {
                $this->notifyAssignment($opportunity, $assignedToUserId);
            }

            return $opportunity;
        });
    }

    public function logManualActivity(User $actor, Opportunity $opportunity, string $type, string $description): OpportunityActivity
    {
        if (! in_array($type, OpportunityActivity::MANUAL_TYPES, true)) {
            throw new InvalidArgumentException("'{$type}' is not a manually loggable activity type.");
        }

        return $this->logActivity($opportunity, $actor, $type, $description);
    }

    private function handleStageChange(User $actor, Opportunity $opportunity, string $fromStageId, string $toStageId): void
    {
        $newStage = OpportunityStage::find($toStageId);

        $type = OpportunityActivity::TYPE_STAGE_CHANGED;
        $description = "Stage changed to {$newStage?->name_en}.";

        if ($newStage?->is_won) {
            $type = OpportunityActivity::TYPE_WON;
            $description = "Marked as Won ({$newStage->name_en}).";
            $this->opportunities->update($opportunity, ['closed_at' => now()]);
        } elseif ($newStage?->is_lost) {
            $type = OpportunityActivity::TYPE_LOST;
            $description = "Marked as Lost ({$newStage->name_en}).";
            $this->opportunities->update($opportunity, ['closed_at' => now()]);
        }

        $this->logActivity($opportunity, $actor, $type, $description, [
            'from_stage_id' => $fromStageId, 'to_stage_id' => $toStageId,
        ]);
    }

    private function logActivity(Opportunity $opportunity, ?User $actor, string $type, string $description, ?array $metadata = null): OpportunityActivity
    {
        return OpportunityActivity::create([
            'tenant_id' => $opportunity->tenant_id,
            'opportunity_id' => $opportunity->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    private function notifyAssignment(Opportunity $opportunity, string $assignedToUserId): void
    {
        $assignee = User::find($assignedToUserId);

        if (! $assignee) {
            return;
        }

        $this->notifications->send(
            $assignee,
            'opportunity.assigned',
            "New opportunity assigned: {$opportunity->opportunity_number}",
            $opportunity->name,
            ['opportunity_id' => $opportunity->id]
        );
    }
}
