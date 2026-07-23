<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Tenant;
use App\Models\User;
use App\Repositories\Contracts\LeadRepositoryInterface;
use App\Repositories\Contracts\LeadStatusRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadService
{
    public function __construct(
        private readonly LeadRepositoryInterface $leads,
        private readonly LeadStatusRepositoryInterface $leadStatuses,
        private readonly SequenceService $sequences,
        private readonly NotificationService $notifications,
    ) {}

    public function create(User $creator, array $data): Lead
    {
        return DB::transaction(function () use ($creator, $data) {
            $data['lead_status_id'] ??= $this->leadStatuses->defaultFor($creator->tenant)?->id;

            if (empty($data['lead_status_id'])) {
                throw new InvalidArgumentException('No default lead status is configured for this company — specify lead_status_id explicitly or set one as default.');
            }

            $lead = $this->leads->create(array_merge($data, [
                'tenant_id' => $creator->tenant_id,
                'lead_number' => $this->sequences->next($creator->tenant_id, 'lead_number', 'LD'),
                'created_by_user_id' => $creator->id,
                'updated_by_user_id' => $creator->id,
                'priority' => $data['priority'] ?? Lead::PRIORITY_NORMAL,
                'probability' => $data['probability'] ?? 0,
            ]));

            $this->logActivity($lead, $creator, LeadActivity::TYPE_CREATED, "Lead {$lead->lead_number} created.");

            if (! empty($data['assigned_to_user_id']) && $data['assigned_to_user_id'] !== $creator->id) {
                $this->notifyAssignment($lead, $data['assigned_to_user_id']);
            }

            return $lead;
        });
    }

    public function update(User $actor, Lead $lead, array $data): Lead
    {
        return DB::transaction(function () use ($actor, $lead, $data) {
            $originalStatusId = $lead->lead_status_id;

            $lead = $this->leads->update($lead, array_merge($data, ['updated_by_user_id' => $actor->id]));

            if (array_key_exists('lead_status_id', $data) && $data['lead_status_id'] !== $originalStatusId) {
                $newStatus = $lead->status()->first();
                $this->logActivity(
                    $lead, $actor, LeadActivity::TYPE_STATUS_CHANGED,
                    "Status changed to {$newStatus?->name_en}.",
                    ['from_status_id' => $originalStatusId, 'to_status_id' => $data['lead_status_id']]
                );
            }

            return $lead;
        });
    }

    public function assign(User $actor, Lead $lead, string $assignedToUserId): Lead
    {
        return DB::transaction(function () use ($actor, $lead, $assignedToUserId) {
            $previousAssigneeId = $lead->assigned_to_user_id;

            if ($previousAssigneeId === $assignedToUserId) {
                return $lead;
            }

            $lead = $this->leads->update($lead, [
                'assigned_to_user_id' => $assignedToUserId,
                'updated_by_user_id' => $actor->id,
            ]);

            $assignee = User::findOrFail($assignedToUserId);

            $this->logActivity(
                $lead, $actor, LeadActivity::TYPE_ASSIGNED,
                "Assigned to {$assignee->full_name}.",
                ['from_user_id' => $previousAssigneeId, 'to_user_id' => $assignedToUserId]
            );

            if ($assignedToUserId !== $actor->id) {
                $this->notifyAssignment($lead, $assignedToUserId);
            }

            return $lead;
        });
    }

    public function logManualActivity(User $actor, Lead $lead, string $type, string $description): LeadActivity
    {
        if (! in_array($type, LeadActivity::MANUAL_TYPES, true)) {
            throw new InvalidArgumentException("'{$type}' is not a manually loggable activity type.");
        }

        return $this->logActivity($lead, $actor, $type, $description);
    }

    private function logActivity(Lead $lead, ?User $actor, string $type, string $description, ?array $metadata = null): LeadActivity
    {
        return LeadActivity::create([
            'tenant_id' => $lead->tenant_id,
            'lead_id' => $lead->id,
            'user_id' => $actor?->id,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    private function notifyAssignment(Lead $lead, string $assignedToUserId): void
    {
        $assignee = User::find($assignedToUserId);

        if (! $assignee) {
            return;
        }

        $this->notifications->send(
            $assignee,
            'lead.assigned',
            "New lead assigned: {$lead->lead_number}",
            trim("{$lead->company_name} — {$lead->first_name} {$lead->last_name}"),
            ['lead_id' => $lead->id]
        );
    }
}
