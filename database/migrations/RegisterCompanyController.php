<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Models\TenantRegistrationRequest;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Public registration no longer provisions a tenant directly — it only
 * files a request. A Super Admin reviews and approves it
 * (see SuperAdmin\PendingRegistrationController::approve), and only at
 * that point does a real tenant/company/user get created and an ID
 * assigned. Nothing here grants login access.
 */
class RegisterCompanyController extends Controller
{
    public function __construct(private readonly TenantRepositoryInterface $tenants) {}

    public function __invoke(RegisterCompanyRequest $request)
    {
        $data = $request->validated();

        if ($this->tenants->subdomainExists($data['subdomain'])
            || TenantRegistrationRequest::where('subdomain', $data['subdomain'])->where('status', 'pending')->exists()) {
            throw ValidationException::withMessages(['subdomain' => 'This subdomain is already taken or pending review.']);
        }

        $registrationRequest = TenantRegistrationRequest::create([
            'legal_name' => $data['legal_name'],
            'subdomain' => $data['subdomain'],
            'trade_name' => $data['trade_name'] ?? null,
            'cr_number' => $data['cr_number'] ?? null,
            'vat_number' => $data['vat_number'] ?? null,
            'admin_full_name' => $data['admin_full_name'] ?? $data['legal_name'].' Owner',
            'admin_email' => $data['admin_email'],
            'admin_password_hash' => Hash::make($data['admin_password']),
            'default_locale' => $data['default_locale'] ?? 'ar',
            'status' => 'pending',
        ]);

        return $this->ok([
            'request_id' => $registrationRequest->id,
            'status' => $registrationRequest->status,
            'message' => 'Thanks — your registration has been submitted for review. You will be notified once a platform administrator approves your account.',
        ], 201);
    }
}
