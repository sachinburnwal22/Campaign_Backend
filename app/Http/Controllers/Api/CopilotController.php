<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CopilotChatRequest;
use App\Http\Resources\CopilotResponseResource;
use App\Services\CopilotService;
use Illuminate\Http\JsonResponse;

class CopilotController extends Controller
{
    protected CopilotService $copilotService;

    /**
     * Inject Copilot orchestrator service.
     */
    public function __construct(CopilotService $copilotService)
    {
        $this->copilotService = $copilotService;
    }

    /**
     * Handle the Copilot marketing strategist chat request.
     */
    public function chat(CopilotChatRequest $request): CopilotResponseResource|JsonResponse
    {
        \Log::info('Copilot Request All: ' . json_encode($request->all()));
        \Log::info('Copilot Request Content: ' . $request->getContent());
        $prompt = $request->getPrompt();

        if (empty($prompt)) {
            return response()->json([
                'success' => false,
                'message' => 'Prompt query is required'
            ], 422);
        }

        // Get authenticated user ID (if auth is active, otherwise null)
        $userId = auth()->id();

        // Delegate entire strategizing and campaign draft logic to service layer
        $result = $this->copilotService->processCopilotChat($prompt, $userId);

        return new CopilotResponseResource($result);
    }
}
