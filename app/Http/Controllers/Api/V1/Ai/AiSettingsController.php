<?php
namespace App\Http\Controllers\Api\V1\Ai;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\UpdateAiSettingsRequest;
use App\Http\Resources\AiSettingResource;
use App\Services\AiSettingsService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AiSettingsController extends Controller
{
    public function __construct(private readonly AiSettingsService $settings) {}

    public function show(Request $request)
    {
        return $this->ok(new AiSettingResource($this->settings->get($request->user()->tenant_id)));
    }

    public function update(UpdateAiSettingsRequest $request)
    {
        try {
            $setting = $this->settings->update($request->user(), $request->validated());
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['provider_override' => $e->getMessage()]);
        }
        return $this->ok(new AiSettingResource($setting));
    }
}
