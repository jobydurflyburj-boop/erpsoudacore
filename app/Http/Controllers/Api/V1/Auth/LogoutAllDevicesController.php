<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class LogoutAllDevicesController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function __invoke(Request $request)
    {
        $this->auth->logoutAllDevices($request->user(), $request);

        return response()->json(null, 204);
    }
}
