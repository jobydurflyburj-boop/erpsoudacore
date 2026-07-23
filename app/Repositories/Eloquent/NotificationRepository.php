<?php

namespace App\Repositories\Eloquent;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Collection;

class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    protected string $modelClass = Notification::class;

    protected array $allowedFilters = ['category'];

    protected array $allowedSorts = ['created_at'];

    protected string $defaultSort = '-created_at';

    public function unreadCountFor(User $user): int
    {
        return Notification::where('user_id', $user->id)->whereNull('read_at')->count();
    }

    public function markAllReadFor(User $user): int
    {
        return Notification::where('user_id', $user->id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function recentFor(User $user, int $limit = 10): Collection
    {
        return Notification::where('user_id', $user->id)->latest('created_at')->limit($limit)->get();
    }
}
