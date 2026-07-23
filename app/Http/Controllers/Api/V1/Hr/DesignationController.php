<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreDesignationRequest;
use App\Http\Requests\Hr\UpdateDesignationRequest;
use App\Http\Resources\DesignationResource;
use App\Models\Designation;
use App\Repositories\Contracts\DesignationRepositoryInterface;
use Illuminate\Http\Request;

class DesignationController extends Controller
{
    public function __construct(private readonly DesignationRepositoryInterface $designations) {}

    public function index(Request $request) { return DesignationResource::collection($this->designations->paginate($request)); }

    public function store(StoreDesignationRequest $request)
    {
        $designation = $this->designations->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new DesignationResource($designation), 201);
    }

    public function update(UpdateDesignationRequest $request, Designation $designation)
    {
        return $this->ok(new DesignationResource($this->designations->update($designation, $request->validated())));
    }
}
