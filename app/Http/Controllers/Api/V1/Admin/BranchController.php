<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Repositories\Contracts\BranchRepositoryInterface;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function __construct(private readonly BranchRepositoryInterface $branches) {}

    public function index(Request $request)
    {
        $paginated = $this->branches->paginate($request);
        $paginated->getCollection()->load('manager');

        return BranchResource::collection($paginated);
    }

    public function store(StoreBranchRequest $request)
    {
        $branch = $this->branches->create(array_merge(
            $request->validated(),
            ['tenant_id' => $request->user()->tenant_id, 'is_active' => $request->boolean('is_active', true)]
        ));

        return $this->ok(new BranchResource($branch->load('manager')), 201);
    }

    public function show(Branch $branch)
    {
        return $this->ok(new BranchResource($branch->load('manager')));
    }

    public function update(UpdateBranchRequest $request, Branch $branch)
    {
        return $this->ok(new BranchResource($this->branches->update($branch, $request->validated())->load('manager')));
    }

    public function destroy(Branch $branch)
    {
        $this->branches->delete($branch);

        return response()->json(null, 204);
    }
}
