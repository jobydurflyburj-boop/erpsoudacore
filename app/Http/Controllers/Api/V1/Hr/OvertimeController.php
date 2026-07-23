<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\StoreOvertimeRequest;
use App\Http\Resources\OvertimeRecordResource;
use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Repositories\Contracts\OvertimeRecordRepositoryInterface;
use App\Services\OvertimeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class OvertimeController extends Controller
{
    public function __construct(
        private readonly OvertimeRecordRepositoryInterface $overtimeRecords,
        private readonly OvertimeService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->overtimeRecords->paginate($request);
        $paginated->getCollection()->load('employee');
        return OvertimeRecordResource::collection($paginated);
    }

    public function store(StoreOvertimeRequest $request)
    {
        $employee = Employee::where('tenant_id', $request->user()->tenant_id)->findOrFail($request->validated()['employee_id']);
        $overtime = $this->service->request($employee, $request->validated());
        return $this->ok(new OvertimeRecordResource($overtime->load('employee')), 201);
    }

    public function approve(Request $request, OvertimeRecord $overtimeRecord)
    {
        try {
            $overtimeRecord = $this->service->approve($request->user(), $overtimeRecord);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new OvertimeRecordResource($overtimeRecord));
    }

    public function reject(Request $request, OvertimeRecord $overtimeRecord)
    {
        try {
            $overtimeRecord = $this->service->reject($request->user(), $overtimeRecord);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }
        return $this->ok(new OvertimeRecordResource($overtimeRecord));
    }
}
