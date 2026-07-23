<?php

namespace App\Http\Controllers\Api\V1\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\ProcessPayrollRequest;
use App\Http\Resources\PayrollRunResource;
use App\Models\PayrollRun;
use App\Repositories\Contracts\PayrollRunRepositoryInterface;
use App\Services\PayrollService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PayrollController extends Controller
{
    public function __construct(
        private readonly PayrollRunRepositoryInterface $payrollRuns,
        private readonly PayrollService $service,
    ) {}

    public function index(Request $request)
    {
        return PayrollRunResource::collection($this->payrollRuns->paginate($request));
    }

    public function show(PayrollRun $payrollRun)
    {
        return $this->ok(new PayrollRunResource($payrollRun->load('payslips.employee')));
    }

    public function process(ProcessPayrollRequest $request)
    {
        try {
            $run = $this->service->process($request->user(), $request->validated()['month'], $request->validated()['year']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['period' => $e->getMessage()]);
        }

        return $this->ok(new PayrollRunResource($run->load('payslips.employee')), 201);
    }

    public function markPaid(PayrollRun $payrollRun)
    {
        try {
            $payrollRun = $this->service->markPaid($payrollRun);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['status' => $e->getMessage()]);
        }

        return $this->ok(new PayrollRunResource($payrollRun->load('payslips.employee')));
    }
}
