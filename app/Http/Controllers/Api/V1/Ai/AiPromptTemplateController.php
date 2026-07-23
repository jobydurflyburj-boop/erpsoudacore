<?php
namespace App\Http\Controllers\Api\V1\Ai;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\UpsertAiPromptTemplateRequest;
use App\Http\Resources\AiPromptTemplateResource;
use App\Models\AiPromptTemplate;
use App\Services\AiPromptService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class AiPromptTemplateController extends Controller
{
    public function __construct(private readonly AiPromptService $prompts) {}

    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $existing = AiPromptTemplate::where('tenant_id', $tenantId)->get()->keyBy('key');

        $rows = collect($this->prompts->validKeys())->map(fn ($key) => [
            'key' => $key,
            'content' => $this->prompts->resolve($tenantId, $key),
            'is_custom' => $existing->has($key),
        ]);

        return $this->ok($rows);
    }

    public function upsert(UpsertAiPromptTemplateRequest $request)
    {
        $v = $request->validated();
        try {
            $template = $this->prompts->upsert($request->user(), $v['key'], $v['content']);
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['key' => $e->getMessage()]);
        }
        return $this->ok(new AiPromptTemplateResource($template));
    }

    public function resetToDefault(Request $request, string $key)
    {
        $this->prompts->resetToDefault($request->user()->tenant_id, $key);
        return $this->ok(['key' => $key, 'content' => $this->prompts->resolve($request->user()->tenant_id, $key), 'is_custom' => false]);
    }
}
