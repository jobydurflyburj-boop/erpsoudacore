<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\User;
use App\Repositories\Contracts\JobApplicationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Basic recruitment pipeline: Job Opening -> Candidate -> Application,
 * with a real status workflow (applied -> screening -> interview ->
 * offered -> hired/rejected). Hiring isn't just a status flag: it's a
 * real integration with the Employee module — marking an application
 * 'hired' creates the actual Employee record from the candidate's
 * details, the same way a Purchase Return auto-generates a linked
 * Debit Note rather than leaving that as a manual follow-up step.
 */
class RecruitmentService
{
    public function __construct(
        private readonly JobApplicationRepositoryInterface $applications,
        private readonly EmployeeService $employees,
    ) {}

    public function updateApplicationStatus(User $actor, JobApplication $application, string $status): JobApplication
    {
        if ($application->status === JobApplication::STATUS_HIRED || $application->status === JobApplication::STATUS_REJECTED) {
            throw new InvalidArgumentException("Application is already {$application->status} and cannot change further.");
        }

        return $this->applications->update($application, ['status' => $status]);
    }

    /**
     * @param array $employeeData hire_date, basic_salary, department_id, designation_id, shift_id — the details recruitment alone doesn't have
     */
    public function hire(User $actor, JobApplication $application, array $employeeData): Employee
    {
        if ($application->status === JobApplication::STATUS_HIRED) {
            throw new InvalidArgumentException('This application has already resulted in a hire.');
        }

        return DB::transaction(function () use ($actor, $application, $employeeData) {
            $candidate = $application->candidate;

            $employee = $this->employees->create($actor, array_merge($employeeData, [
                'full_name' => $candidate->full_name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
            ]));

            $this->applications->update($application, ['status' => JobApplication::STATUS_HIRED]);

            return $employee;
        });
    }
}
