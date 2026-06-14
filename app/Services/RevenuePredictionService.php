<?php

namespace App\Services;

class RevenuePredictionService
{
    /**
     * Calculate predicted conversion rate and revenue opportunity.
     */
    public function predict(int $audienceSize, float $averageOrderValue, ?float $averageEngagementScore = null): array
    {
        // Baseline conversion rate: 4%
        $conversionRate = 0.04;

        if ($averageEngagementScore !== null) {
            // Adjust conversion rate based on engagement score
            if ($averageEngagementScore >= 80) {
                $conversionRate = 0.06;
            } elseif ($averageEngagementScore >= 50) {
                $conversionRate = 0.045;
            } else {
                $conversionRate = 0.03;
            }
        }

        // Expected revenue: Audience size * Conversion rate * Average order value
        $predictedRevenue = round($audienceSize * $conversionRate * $averageOrderValue, 2);

        return [
            'predicted_conversion_rate' => $conversionRate * 100, // returned as percentage (e.g. 4)
            'predicted_revenue' => $predictedRevenue,
        ];
    }
}
