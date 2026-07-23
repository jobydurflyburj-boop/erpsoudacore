<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends Controller
{
    /**
     * Returns every (category, channel) pair the frontend needs to render
     * a preferences grid, with 'enabled' resolved from an explicit row if
     * one exists, or the same default NotificationService::channelEnabled
     * applies (email on, everything else off) — kept in sync manually
     * since one is a read path and the other a send-time decision; see
     * the shared default note on notification_preferences' migration.
     */
    public function index(Request $request)
    {
        $categories = ['user.invited', 'role.changed', 'task.assigned', 'approval.pending'];
        $existing = NotificationPreference::where('user_id', $request->user()->id)
            ->get()
            ->keyBy(fn ($p) => "{$p->category}:{$p->channel}");

        $result = [];

        foreach ($categories as $category) {
            foreach (NotificationPreference::CHANNELS as $channel) {
                $key = "{$category}:{$channel}";
                $result[] = [
                    'category' => $category,
                    'channel' => $channel,
                    'enabled' => $existing->has($key) ? $existing->get($key)->enabled : ($channel === 'email'),
                ];
            }
        }

        return $this->ok($result);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'preferences' => ['required', 'array'],
            'preferences.*.category' => ['required', 'string'],
            'preferences.*.channel' => ['required', Rule::in(NotificationPreference::CHANNELS)],
            'preferences.*.enabled' => ['required', 'boolean'],
        ]);

        foreach ($data['preferences'] as $pref) {
            NotificationPreference::updateOrCreate(
                [
                    'tenant_id' => $request->user()->tenant_id,
                    'user_id' => $request->user()->id,
                    'category' => $pref['category'],
                    'channel' => $pref['channel'],
                ],
                ['enabled' => $pref['enabled']]
            );
        }

        return $this->ok(['message' => 'Notification preferences updated.']);
    }
}
