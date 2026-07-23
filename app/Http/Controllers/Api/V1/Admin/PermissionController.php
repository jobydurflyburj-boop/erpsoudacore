<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Repositories\Contracts\PermissionRepositoryInterface;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionRepositoryInterface $permissions) {}

    public function index()
    {
        // Grouped by module — this is what the role-builder UI in the
        // frontend renders directly as a checklist per module.
        $grouped = $this->permissions->allGroupedByModule()
            ->map(fn ($permissions) => PermissionResource::collection($permissions));

        return $this->ok($grouped);
    }
}
