<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function index(Request $request)
    {
        $paginated = $this->notifications->query()
            ->where('user_id', $request->user()->id)
            ->latest('created_at')
            ->paginate((int) $request->integer('page_size', 25));

        return NotificationResource::collection($paginated);
    }

    public function unreadCount(Request $request)
    {
        return $this->ok(['unread_count' => $this->notifications->unreadCountFor($request->user())]);
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->update(['read_at' => now()]);

        return $this->ok(new NotificationResource($notification));
    }

    public function markAllRead(Request $request)
    {
        $count = $this->notifications->markAllReadFor($request->user());

        return $this->ok(['marked_read' => $count]);
    }

    public function destroy(Request $request, Notification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        return response()->json(null, 204);
    }
}
