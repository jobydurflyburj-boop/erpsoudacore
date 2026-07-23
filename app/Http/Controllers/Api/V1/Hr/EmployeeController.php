<?php

namespace App\Http\Controllers\Api\V1\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\AssignSalaryComponentsRequest;
use App\Http\Requests\Hr\StoreEmployeeRequest;
use App\Http\Requests\Hr\TerminateEmployeeRequest;
use App\Http\Requests\Hr\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EmployeeService $service,
    ) {}

    public function index(Request $request)
    {
        $paginated = $this->employees->paginate($request);
        $paginated->getCollection()->load(['department', 'designation', 'shift']);

        return EmployeeResource::collection($paginated);
    }

    public function store(StoreEmployeeRequest $request)
    {
        $employee = $this->service->create($request->user(), $request->validated());

        return $this->ok(new EmployeeResource($employee->load(['department', 'designation', 'shift'])), 201);
    }

    public function show(Employee $employee)
    {
        return $this->ok(new EmployeeResource($employee->load(['department', 'designation', 'shift', 'salaryComponents.salaryComponent'])));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        return $this->ok(new EmployeeResource($this->service->update($employee, $request->validated())->load(['department', 'designation', 'shift'])));
    }

    public function terminate(TerminateEmployeeRequest $request, Employee $employee)
    {
        try {
            $employee = $this->service->terminate($employee, $request->validated()['termination_date']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new EmployeeResource($employee));
    }

    public function assignSalaryComponents(AssignSalaryComponentsRequest $request, Employee $employee)
    {
        $employee = $this->service->assignSalaryComponents($employee, $request->validated()['components']);

        return $this->ok(new EmployeeResource($employee));
    }
}
