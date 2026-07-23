<?php

namespace App\Http\Controllers\Api\V1\Platform;

use App\Http\Controllers\Controller;
use App\Models\PushDeviceToken;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushDeviceTokenController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['required', Rule::in(['ios', 'android', 'web'])],
        ]);

        PushDeviceToken::updateOrCreate(
            ['user_id' => $request->user()->id, 'token' => $data['token']],
            ['tenant_id' => $request->user()->tenant_id, 'platform' => $data['platform'], 'last_used_at' => now()]
        );

        return $this->ok(['message' => 'Device registered for push notifications.'], 201);
    }

    public function destroy(Request $request, string $token)
    {
        PushDeviceToken::where('user_id', $request->user()->id)->where('token', $token)->delete();

        return response()->json(null, 204);
    }
}
