<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreLeaveTypeRequest;
use App\Http\Resources\LeaveTypeResource;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function __construct(private readonly LeaveTypeRepositoryInterface $leaveTypes) {}

    public function index(Request $request) { return LeaveTypeResource::collection($this->leaveTypes->paginate($request)); }

    public function store(StoreLeaveTypeRequest $request)
    {
        $leaveType = $this->leaveTypes->create(array_merge($request->validated(), ['tenant_id' => $request->user()->tenant_id]));
        return $this->ok(new LeaveTypeResource($leaveType), 201);
    }
}
