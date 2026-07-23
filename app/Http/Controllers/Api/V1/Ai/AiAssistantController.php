<?php
namespace App\Http\Controllers\Api\V1\Ai;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\AskAiRequest;
use App\Http\Resources\AiConversationResource;
use App\Models\AiConversation;
use App\Services\Ai\LlmProviderInterface;
use App\Services\AiAssistantService;
use Illuminate\Http\Request;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $assistant,
        private readonly LlmProviderInterface $llm,
    ) {}

    public function status()
    {
        return $this->ok([
            'provider' => $this->llm->name(),
            'configured' => $this->llm->isConfigured(),
            'model' => $this->llm->isConfigured() ? $this->llm->model() : null,
        ]);
    }

    public function conversations(Request $request)
    {
        $conversations = AiConversation::where('user_id', $request->user()->id)->latest('created_at')->get();
        return $this->ok(AiConversationResource::collection($conversations));
    }

    public function show(AiConversation $conversation)
    {
        return $this->ok(new AiConversationResource($conversation->load('messages')));
    }

    public function ask(AskAiRequest $request)
    {
        $conversation = $request->validated('conversation_id')
            ? AiConversation::findOrFail($request->validated('conversation_id'))
            : null;

        $result = $this->assistant->ask($request->user(), $request->validated('message'), $conversation);

        return $this->ok(new AiConversationResource($result['conversation']->fresh('messages')), 201);
    }
}
