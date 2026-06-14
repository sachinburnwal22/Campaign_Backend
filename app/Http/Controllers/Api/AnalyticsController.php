<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get global analytics summary.
     */
    public function overview(): JsonResponse
    {
        $data = $this->analyticsService->getOverview();

        return response()->json($data);
    }

    /**
     * Get analytics metrics for a single campaign.
     */
    public function campaign(int $id): JsonResponse
    {
        $data = $this->analyticsService->getCampaignAnalytics($id);

        return response()->json($data);
    }
}
