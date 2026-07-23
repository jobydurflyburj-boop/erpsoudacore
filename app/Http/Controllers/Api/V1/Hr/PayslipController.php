<?php
namespace App\Http\Controllers\Api\V1\Hr;
use App\Http\Controllers\Controller;
use App\Http\Resources\PayslipResource;
use App\Models\Payslip;
use App\Repositories\Contracts\PayslipRepositoryInterface;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function __construct(private readonly PayslipRepositoryInterface $payslips) {}

    public function index(Request $request)
    {
        $paginated = $this->payslips->paginate($request);
        $paginated->getCollection()->load('employee');
        return PayslipResource::collection($paginated);
    }

    public function show(Payslip $payslip)
    {
        return $this->ok(new PayslipResource($payslip->load(['employee', 'lines'])));
    }
}
