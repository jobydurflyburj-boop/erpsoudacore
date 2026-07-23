<?php

namespace App\Repositories\Eloquent;

use App\Models\PasswordHistory;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    protected string $modelClass = User::class;

    protected array $allowedFilters = ['status', 'role_id', 'department_id', 'company_id'];

    protected array $allowedSorts = ['created_at', 'full_name', 'last_login_at'];

    protected array $searchableFields = ['full_name', 'email', 'phone'];

    public function findByEmailForTenant(string $email, string $tenantId): ?User
    {
        return User::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenantId)
            ->where('email', $email)
            ->first();
    }

    public function recentPasswordHashes(User $user, int $limit): Collection
    {
        return PasswordHistory::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->pluck('password_hash');
    }

    public function findSuperAdminByEmail(string $email): ?User
    {
        return User::withoutGlobalScope('tenant')
            ->whereNull('tenant_id')
            ->where('email', $email)
            ->first();
    }
}
