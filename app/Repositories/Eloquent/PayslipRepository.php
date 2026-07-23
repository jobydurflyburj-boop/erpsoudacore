<?php
namespace App\Repositories\Eloquent;
use App\Models\Payslip;
use App\Repositories\Contracts\PayslipRepositoryInterface;
class PayslipRepository extends BaseRepository implements PayslipRepositoryInterface
{
    protected string $modelClass = Payslip::class;
    protected array $allowedFilters = ['payroll_run_id', 'employee_id', 'status'];
    protected array $allowedSorts = ['created_at'];
}
