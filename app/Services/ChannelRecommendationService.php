<?php

namespace App\Services;

class ChannelRecommendationService
{
    /**
     * Recommend the best communication channel (whatsapp, email, sms, rcs) based on rules and prompt context.
     */
    public function recommend(array $rules, string $prompt = '', ?float $avgEngagementScore = null): array
    {
        $channel = 'whatsapp'; // default channel
        $confidence = 85;
        $reason = 'WhatsApp is recommended as it has the highest open rates for standard customer outreach.';

        $isInactive = isset($rules['inactive_days']) || (isset($rules['last_order_days']) && $this->extractValue($rules['last_order_days']) > 60);
        $isHighEngagement = ($avgEngagementScore !== null && $avgEngagementScore >= 75) || isset($rules['engagement_score']);
        
        $promptLower = strtolower($prompt);
        $isUrgent = str_contains($promptLower, 'urgent') || str_contains($promptLower, 'expire') || str_contains($promptLower, 'limited time') || str_contains($promptLower, 'flash') || str_contains($promptLower, 'now');
        $isProfessional = str_contains($promptLower, 'newsletter') || str_contains($promptLower, 'report') || str_contains($promptLower, 'invoice') || str_contains($promptLower, 'update') || str_contains($promptLower, 'weekly') || str_contains($promptLower, 'monthly');

        if ($isUrgent) {
            $channel = 'sms';
            $confidence = 90;
            $reason = 'Urgent offers and time-sensitive discounts are best delivered via SMS for immediate reading.';
        } elseif ($isProfessional) {
            $channel = 'email';
            $confidence = 88;
            $reason = 'Email is ideal for longer, formal updates, newsletters, or professional communications.';
        } elseif ($isInactive) {
            $channel = 'whatsapp';
            $confidence = 92;
            $reason = 'WhatsApp generally delivers higher engagement and recovery rates for inactive high-value customers.';
        } elseif ($isHighEngagement) {
            $channel = 'whatsapp';
            $confidence = 95;
            $reason = 'High engagement customers respond best to rich messaging channels like WhatsApp.';
        }

        return [
            'recommended_channel' => $channel,
            'confidence' => $confidence,
            'reason' => $reason,
        ];
    }

    private function extractValue($rule)
    {
        if (is_array($rule) && isset($rule['value'])) {
            return $rule['value'];
        }
        return $rule;
    }
}
