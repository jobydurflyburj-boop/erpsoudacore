<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Repositories\Contracts\DepartmentRepositoryInterface;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentRepositoryInterface $departments) {}

    public function index(Request $request)
    {
        $paginated = $this->departments->paginate($request);
        $paginated->getCollection()->load('manager')->loadCount('users');

        return DepartmentResource::collection($paginated);
    }

    public function store(StoreDepartmentRequest $request)
    {
        $department = $this->departments->create(array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id]
        ));

        return $this->ok(new DepartmentResource($department->load('manager')), 201);
    }

    public function show(Department $department)
    {
        return $this->ok(new DepartmentResource($department->load('manager')->loadCount('users')));
    }

    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        return $this->ok(new DepartmentResource($this->departments->update($department, $request->validated())->load('manager')));
    }

    public function destroy(Department $department)
    {
        $this->departments->delete($department);

        return response()->json(null, 204);
    }
}
