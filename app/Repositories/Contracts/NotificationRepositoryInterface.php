<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface extends RepositoryInterface
{
    public function unreadCountFor(User $user): int;

    public function markAllReadFor(User $user): int;

    public function recentFor(User $user, int $limit = 10): Collection;
}
