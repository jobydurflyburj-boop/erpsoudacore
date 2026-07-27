<?php

namespace App\Http\Controllers\Api\V1\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\TenantRegistrationRequest;
use App\Services\RegistrationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PendingRegistrationController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');

        $requests = TenantRegistrationRequest::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate($request->integer('page_size', 25));

        return $this->ok($requests);
    }

    public function approve(Request $request, TenantRegistrationRequest $registrationRequest)
    {
        if ($registrationRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'This request has already been reviewed.']);
        }

        $result = $this->registration->approveRegistrationRequest($registrationRequest, $request->user());

        return $this->ok([
            'tenant_id' => $result['tenant']->id,
            'company_id' => $result['company']->id,
            'user_id' => $result['user']->id,
            'message' => 'Approved — tenant provisioned.',
        ]);
    }

    public function reject(Request $request, TenantRegistrationRequest $registrationRequest)
    {
        if ($registrationRequest->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'This request has already been reviewed.']);
        }

        $registrationRequest->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('reason'),
        ]);

        return $this->ok(['message' => 'Registration request rejected.']);
    }
}
