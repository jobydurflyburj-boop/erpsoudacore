<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Services\UserService;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ChangePasswordController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function __invoke(ChangePasswordRequest $request)
    {
        try {
            $this->users->changePassword(
                $request->user(),
                $request->string('current_password'),
                $request->string('new_password')
            );
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['current_password' => $e->getMessage()]);
        }

        return $this->ok(['message' => 'Password changed. All other sessions have been logged out for security.']);
    }
}
