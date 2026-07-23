<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Services\RegistrationService;

class RegisterCompanyController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    public function __invoke(RegisterCompanyRequest $request)
    {
        $result = $this->registration->registerCompany($request->validated());

        return $this->ok([
            'tenant' => new TenantResource($result['tenant']),
            'company' => new CompanyResource($result['company']),
            'user' => new UserResource($result['user']),
            'message' => 'Company registered. Please check your email to verify your account.',
        ], 201);
    }
}
