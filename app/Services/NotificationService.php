<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * In-app persistence is unconditional — every notification always lands
 * in the recipient's inbox (notifications table), regardless of channel
 * preferences, since that's the one channel with no delivery-failure mode
 * and no external dependency. Email/SMS/WhatsApp/push are then dispatched
 * ADDITIONALLY per the user's per-category channel preferences
 * (notification_preferences — see that table's comment for why absence
 * of a row means "use the default", not "disabled").
 *
 * SMS/WhatsApp/push transport are TODO(ops) gaps the same way OTP SMS is
 * — the preference logic, queuing, and delivery-attempt structure are
 * all real; only the external gateway credentials are a deployment-time
 * concern this environment doesn't have.
 */
class NotificationService
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function send(User $recipient, string $category, string $title, ?string $body = null, array $data = []): Notification
    {
        $notification = $this->notifications->create([
            'tenant_id' => $recipient->tenant_id,
            'user_id' => $recipient->id,
            'category' => $category,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        foreach (['email', 'sms', 'whatsapp', 'push'] as $channel) {
            if ($this->channelEnabled($recipient, $category, $channel)) {
                $this->dispatchToChannel($recipient, $channel, $title, $body);
            }
        }

        return $notification;
    }

    private function channelEnabled(User $user, string $category, string $channel): bool
    {
        $preference = NotificationPreference::where('user_id', $user->id)
            ->where('category', $category)
            ->where('channel', $channel)
            ->first();

        // No explicit preference row = default. Email defaults ON
        // (matches how invitations/verification already behave); SMS,
        // WhatsApp, and push default OFF — those have a real per-message
        // cost and shouldn't fire until a tenant deliberately opts in.
        if ($preference === null) {
            return $channel === 'email';
        }

        return $preference->enabled;
    }

    private function dispatchToChannel(User $user, string $channel, string $title, ?string $body): void
    {
        match ($channel) {
            'email' => $this->sendEmail($user, $title, $body),
            'sms' => $this->sendSms($user, $title, $body),
            'whatsapp' => $this->sendWhatsapp($user, $title, $body),
            'push' => $this->sendPush($user, $title, $body),
            default => null,
        };
    }

    private function sendEmail(User $user, string $title, ?string $body): void
    {
        Mail::to($user->email)->queue(new NotificationMail($title, $body));
    }

    private function sendSms(User $user, string $title, ?string $body): void
    {
        if (! $user->phone) {
            return;
        }

        // TODO(ops): real Saudi SMS gateway (Unifonic/Msegat) — same
        // driver pattern as OtpService::send(). Logged for now so
        // preference/queuing logic is fully exercisable in local/dev.
        Log::info("[SMS] To {$user->phone}: {$title}");
    }

    private function sendWhatsapp(User $user, string $title, ?string $body): void
    {
        if (! $user->phone) {
            return;
        }

        // TODO(ops): WhatsApp Business Platform (Cloud API) integration.
        Log::info("[WhatsApp] To {$user->phone}: {$title}");
    }

    private function sendPush(User $user, string $title, ?string $body): void
    {
        $tokens = $user->pushDeviceTokens()->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        // TODO(ops): FCM (Android/web) + APNs (iOS) delivery.
        Log::info("[Push] To {$tokens->count()} device(s) for {$user->email}: {$title}");
    }
}
