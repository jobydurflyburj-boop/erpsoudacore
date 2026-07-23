<?php
namespace App\Http\Controllers\Api\V1\Ai;
use App\Http\Controllers\Controller;
use App\Http\Resources\AiSuggestionResource;
use App\Models\AiSuggestion;
use App\Repositories\Contracts\AiSuggestionRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AiSuggestionController extends Controller
{
    public function __construct(private readonly AiSuggestionRepositoryInterface $suggestions) {}

    public function index(Request $request) { return AiSuggestionResource::collection($this->suggestions->paginate($request)); }

    public function dismiss(AiSuggestion $aiSuggestion)
    {
        if ($aiSuggestion->status !== AiSuggestion::STATUS_OPEN) {
            throw ValidationException::withMessages(['status' => "Suggestion is already {$aiSuggestion->status}."]);
        }
        $this->suggestions->update($aiSuggestion, ['status' => AiSuggestion::STATUS_DISMISSED, 'dismissed_at' => now()]);
        return $this->ok(new AiSuggestionResource($aiSuggestion->fresh()));
    }

    public function markActioned(AiSuggestion $aiSuggestion)
    {
        if ($aiSuggestion->status !== AiSuggestion::STATUS_OPEN) {
            throw ValidationException::withMessages(['status' => "Suggestion is already {$aiSuggestion->status}."]);
        }
        $this->suggestions->update($aiSuggestion, ['status' => AiSuggestion::STATUS_ACTIONED]);
        return $this->ok(new AiSuggestionResource($aiSuggestion->fresh()));
    }
}
