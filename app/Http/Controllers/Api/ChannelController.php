<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ChannelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    protected ChannelService $channelService;

    public function __construct(ChannelService $channelService)
    {
        $this->channelService = $channelService;
    }

    /**
     * Simulated external channel API.
     */
    public function send(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'communication_id' => 'required|integer',
            'customer' => 'required|array',
            'message' => 'required|string',
            'channel' => 'required|string',
        ]);

        // Triggers the progressive callback generator
        $this->channelService->sendMessage(
            $validated['communication_id'],
            $validated['customer'],
            $validated['message'],
            $validated['channel']
        );

        return response()->json([
            'success' => true,
            'message' => 'Simulated message accepted by channel service',
        ]);
    }
}
