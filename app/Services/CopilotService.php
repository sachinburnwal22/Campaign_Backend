<?php

namespace App\Services;

use App\Repositories\CampaignRepositoryInterface;
use App\Repositories\SegmentRepositoryInterface;
use App\Jobs\LogAiConversationJob;

class CopilotService
{
    protected GeminiService $geminiService;
    protected SegmentService $segmentService;
    protected RevenuePredictionService $revenuePredictionService;
    protected ChannelRecommendationService $channelRecommendationService;
    protected SegmentRepositoryInterface $segmentRepository;
    protected CampaignRepositoryInterface $campaignRepository;

    public function __construct(
        GeminiService $geminiService,
        SegmentService $segmentService,
        RevenuePredictionService $revenuePredictionService,
        ChannelRecommendationService $channelRecommendationService,
        SegmentRepositoryInterface $segmentRepository,
        CampaignRepositoryInterface $campaignRepository
    ) {
        $this->geminiService = $geminiService;
        $this->segmentService = $segmentService;
        $this->revenuePredictionService = $revenuePredictionService;
        $this->channelRecommendationService = $channelRecommendationService;
        $this->segmentRepository = $segmentRepository;
        $this->campaignRepository = $campaignRepository;
    }

    /**
     * Run the Copilot marketing intelligence engine.
     */
    public function processCopilotChat(string $prompt, ?int $userId = null): array
    {
        // 1. Convert natural language prompt to structured marketing JSON via Gemini
        $config = $this->geminiService->generateCampaignConfig($prompt);

        // 2. Dispatch a database queue job to log the AI conversation history asynchronously
        dispatch(new LogAiConversationJob($prompt, $config, $userId));

        // 3. Compile AI-generated rules into an Eloquent query
        $rules = $config['rules'] ?? [];
        $query = $this->segmentService->getCustomersQuery($rules);

        // 4. Calculate matching audience stats
        $audienceStats = $this->segmentService->getAudienceStats($query);

        // 5. Predict revenue opportunity and conversion rate
        $prediction = $this->revenuePredictionService->predict(
            $audienceStats['audience_size'],
            $audienceStats['average_order_value'],
            $audienceStats['average_engagement_score']
        );

        // 6. Recommend best communication channel and confidence level
        $channelRecommendation = $this->channelRecommendationService->recommend(
            $rules,
            $prompt,
            $audienceStats['average_engagement_score']
        );

        // 7. Extract copy details and handle placeholders
        $headline = $config['headline'] ?? 'Special Promotion';
        $body = $config['body'] ?? ($config['message'] ?? '');
        $cta = $config['cta'] ?? 'Shop Now';
        $subject = $config['subject'] ?? ($config['campaign_goal'] ?? 'Special Offer');

        // Create the segment record in database
        $segment = $this->segmentRepository->create([
            'name' => $config['segment_name'],
            'description' => $config['segment_description'] ?? 'AI-generated segment',
            'rules_json' => $rules,
        ]);

        // 8. Create the Campaign Draft (no communications sent yet)
        $campaignDraft = $this->campaignRepository->create([
            'name' => $config['segment_name'] . ' Campaign',
            'segment_id' => $segment->id,
            'channel' => $channelRecommendation['recommended_channel'],
            'message' => $body,
            'status' => 'draft',
            'expected_revenue' => $prediction['predicted_revenue'],
            'predicted_revenue' => $prediction['predicted_revenue'],
            'predicted_conversion_rate' => $prediction['predicted_conversion_rate'],
        ]);

        // Map segment rules to human-readable strings for display and compatibility
        $segmentRules = [];
        foreach ($rules as $key => $rule) {
            $op = $rule['operator'] ?? '=';
            $val = $rule['value'] ?? '';
            
            if ($key === 'total_spent') $segmentRules[] = "lifetime_spent {$op} ₹{$val}";
            elseif ($key === 'inactive_days') $segmentRules[] = "days_since_last_order {$op} {$val}";
            elseif ($key === 'city') $segmentRules[] = "city {$op} {$val}";
            elseif ($key === 'gender') $segmentRules[] = "gender {$op} {$val}";
            elseif ($key === 'order_count') $segmentRules[] = "order_count {$op} {$val}";
            elseif ($key === 'last_order_days') $segmentRules[] = "days_since_last_order {$op} {$val}";
            elseif ($key === 'engagement_score') $segmentRules[] = "engagement_score {$op} {$val}";
            elseif ($key === 'age') $segmentRules[] = "age {$op} {$val}";
            elseif ($key === 'date_of_birth') $segmentRules[] = "date_of_birth {$op} {$val}";
        }

        // Return unified Copilot payload (Step 10 and backward compatibility)
        return [
            'segment' => [
                'id' => $segment->id,
                'segment_name' => $segment->name,
                'segment_description' => $segment->description,
                'rules' => $rules,
            ],
            'audience' => $audienceStats,
            'prediction' => $prediction,
            'channel' => $channelRecommendation,
            'message' => [
                'subject' => $subject,
                'headline' => $headline,
                'body' => $body,
                'cta' => $cta,
            ],
            'campaign_draft' => [
                'id' => $campaignDraft->id,
                'name' => $campaignDraft->name,
                'segment_id' => $campaignDraft->segment_id,
                'channel' => $campaignDraft->channel,
                'message' => $campaignDraft->message,
                'status' => $campaignDraft->status,
                'expected_revenue' => $campaignDraft->expected_revenue,
                'predicted_revenue' => $campaignDraft->predicted_revenue,
                'predicted_conversion_rate' => $campaignDraft->predicted_conversion_rate,
            ],
            // Backward compatibility with existing Next.js frontend UI
            'campaign' => [
                'name' => $campaignDraft->name,
                'audience' => $audienceStats['audience_size'],
                'audienceType' => $segment->name,
                'channels' => [ucfirst($channelRecommendation['recommended_channel'])],
                'channelExplanation' => $channelRecommendation['reason'],
                'revenue' => $prediction['predicted_revenue'],
                'message' => $body,
                'segmentRules' => $segmentRules,
                'rules' => $rules,
                'openRate' => 0.78,
                'ctrRate' => 0.34,
                'conversionRate' => $prediction['predicted_conversion_rate'] / 100,
            ]
        ];
    }
}
