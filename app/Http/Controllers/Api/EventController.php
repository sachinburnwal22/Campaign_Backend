<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\CampaignEventRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    protected CampaignEventRepositoryInterface $eventRepository;

    public function __construct(CampaignEventRepositoryInterface $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * Get live campaign activity stream.
     */
    public function stream(Request $request): JsonResponse
    {
        $limit = $request->query('limit', 20);
        $events = $this->eventRepository->getLatest(intval($limit));

        $formatted = $events->map(function ($event) {
            $comm = $event->communication;
            $customer = $comm->customer ?? null;
            $campaign = $comm->campaign ?? null;

            $amount = null;
            if ($event->event_type === 'converted' && isset($event->details['amount'])) {
                $amount = '₹' . number_format($event->details['amount']);
            }

            return [
                'id' => $event->id,
                'kind' => $event->event_type,
                'name' => $customer->name ?? 'Unknown Customer',
                'city' => $customer->city ?? 'Unknown City',
                'campaign' => $campaign->name ?? 'Unknown Campaign',
                'amount' => $amount,
                'timestamp' => $event->created_at->toIso8601String(),
            ];
        });

        return response()->json($formatted);
    }
}
