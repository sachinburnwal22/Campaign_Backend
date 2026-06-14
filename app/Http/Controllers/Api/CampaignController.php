<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Jobs\SendCommunicationJob;
use App\Models\Campaign;
use App\Models\Communication;
use App\Repositories\CampaignRepositoryInterface;
use App\Services\SegmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    protected CampaignRepositoryInterface $campaignRepository;
    protected SegmentService $segmentService;

    public function __construct(CampaignRepositoryInterface $campaignRepository, SegmentService $segmentService)
    {
        $this->campaignRepository = $campaignRepository;
        $this->segmentService = $segmentService;
    }

    public function index(): JsonResponse
    {
        $campaigns = $this->campaignRepository->paginate(100);

        return response()->json($campaigns);
    }

    public function store(StoreCampaignRequest $request): JsonResponse
    {
        $campaign = $this->campaignRepository->create($request->validated());

        return response()->json($campaign->load('segment'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $campaign = $this->campaignRepository->find($id);

        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        return response()->json($campaign);
    }

    public function update(StoreCampaignRequest $request, int $id): JsonResponse
    {
        $campaign = $this->campaignRepository->update($id, $request->validated());

        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        return response()->json($campaign->load('segment'));
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->campaignRepository->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        return response()->json(['message' => 'Campaign deleted successfully']);
    }

    /**
     * Launch the campaign: matches segment rules, creates communications, and queues dispatch jobs.
     */
    public function launch(int $id): JsonResponse
    {
        $campaign = Campaign::with('segment')->find($id);

        if (!$campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        if ($campaign->status === 'running' || $campaign->status === 'completed') {
            return response()->json(['message' => 'Campaign has already been launched'], 400);
        }

        $segment = $campaign->segment;
        if (!$segment) {
            return response()->json(['message' => 'Segment associated with campaign not found'], 400);
        }

        // Fetch segment matches
        $customers = $this->segmentService->getCustomers($segment->rules_json);

        if ($customers->isEmpty()) {
            return response()->json(['message' => 'No customers match the selected segment criteria'], 400);
        }

        Log::info("Launching campaign #{$campaign->id} ({$campaign->name}) for " . $customers->count() . " customers.");

        DB::transaction(function () use ($campaign, $customers) {
            // Update status to running
            $campaign->update(['status' => 'running']);

            // Create and queue communications
            foreach ($customers as $customer) {
                // Personalize the template variables
                $nameParts = explode(' ', trim($customer->name));
                $firstName = $nameParts[0] ?? $customer->name;
                $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

                $replacements = [
                    '{{first_name}}' => $firstName,
                    '{{last_name}}' => $lastName,
                    '{{name}}' => $customer->name,
                    '{{city}}' => $customer->city,
                    '{{store_link}}' => 'http://localhost:3000',
                    '{{cart_link}}' => 'http://localhost:3000/cart',
                    '{{order_count}}' => $customer->orders ? $customer->orders->count() : rand(2, 8),
                ];

                $personalizedMessage = str_replace(array_keys($replacements), array_values($replacements), $campaign->message);
                
                // Fallback cleanup: if they typed custom braced items that match name/city (e.g. {{Girish}} or {{Phagwara}}),
                // replace them with the actual values dynamically!
                if (preg_match_all('/\{\{([^}]+)\}\}/', $personalizedMessage, $matches)) {
                    foreach ($matches[1] as $match) {
                        $cleanedMatch = trim($match);
                        if (strcasecmp($cleanedMatch, $customer->name) === 0 || strcasecmp($cleanedMatch, $firstName) === 0) {
                            $personalizedMessage = str_replace('{{' . $match . '}}', $firstName, $personalizedMessage);
                        } elseif (strcasecmp($cleanedMatch, $customer->city) === 0) {
                            $personalizedMessage = str_replace('{{' . $match . '}}', $customer->city, $personalizedMessage);
                        }
                    }
                }

                $communication = Communication::create([
                    'campaign_id' => $campaign->id,
                    'customer_id' => $customer->id,
                    'channel' => $campaign->channel,
                    'message' => $personalizedMessage,
                    'status' => 'sent',
                    'sent_at' => Carbon::now(),
                ]);

                // Dispatch async sending job
                SendCommunicationJob::dispatch($communication->id);
            }
        });

        return response()->json([
            'message' => 'Campaign launched successfully',
            'audience_count' => $customers->count(),
        ]);
    }
}
