<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\MarkAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Employee;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use App\Services\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendances,
        private readonly AttendanceService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->attendances->paginate($request);
        $paginated->getCollection()->load('employee');
        return AttendanceResource::collection($paginated);
    }

    public function mark(MarkAttendanceRequest $request)
    {
        $employee = Employee::where('tenant_id', $request->user()->tenant_id)->findOrFail($request->validated()['employee_id']);
        $attendance = $this->service->mark($employee, $request->validated()['date'], $request->validated()['status']);
        return $this->ok(new AttendanceResource($attendance->load('employee')), 201);
    }
}
