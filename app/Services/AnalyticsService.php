<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\Communication;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Get global analytics overview.
     */
    public function getOverview(): array
    {
        return $this->getMetrics();
    }

    /**
     * Get analytics for a specific campaign.
     */
    public function getCampaignAnalytics(int $campaignId): array
    {
        return $this->getMetrics($campaignId);
    }

    /**
     * Compute performance metrics.
     */
    private function getMetrics(?int $campaignId = null): array
    {
        // Base query for communications
        $commQuery = Communication::query();
        if ($campaignId) {
            $commQuery->where('campaign_id', $campaignId);
        }

        // Get status counts in one query
        $statusCounts = $commQuery->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $sent = array_sum($statusCounts);
        $failed = $statusCounts['failed'] ?? 0;
        $delivered = $sent - $failed;

        $opened = ($statusCounts['opened'] ?? 0)
            + ($statusCounts['read'] ?? 0)
            + ($statusCounts['clicked'] ?? 0)
            + ($statusCounts['converted'] ?? 0);

        $clicked = ($statusCounts['clicked'] ?? 0)
            + ($statusCounts['converted'] ?? 0);

        $converted = $statusCounts['converted'] ?? 0;

        $conversionRate = $sent > 0 ? round(($converted / $sent) * 100, 2) : 0.00;
        $openRate = $delivered > 0 ? round(($opened / $delivered) * 100, 2) : 0.00;
        $ctrRate = $opened > 0 ? round(($clicked / $opened) * 100, 2) : 0.00;

        // Compute revenue from campaign_events
        $eventQuery = CampaignEvent::where('event_type', 'converted');
        if ($campaignId) {
            $eventQuery->whereHas('communication', function ($q) use ($campaignId) {
                $q->where('campaign_id', $campaignId);
            });
        }

        // Parse jsonb details amount
        // In PostgreSQL: CAST(details->>'amount' AS NUMERIC)
        // In SQLite: CAST(json_extract(details, '$.amount') AS NUMERIC)
        $driver = DB::connection()->getDriverName();
        if ($driver === 'pgsql') {
            $amountExpression = "COALESCE(SUM(CAST(details->>'amount' AS NUMERIC)), 0)";
        } elseif ($driver === 'mysql' || $driver === 'mariadb') {
            $amountExpression = "COALESCE(SUM(CAST(json_extract(details, '$.amount') AS DECIMAL(15,2))), 0)";
        } else {
            $amountExpression = "COALESCE(SUM(CAST(json_extract(details, '$.amount') AS NUMERIC)), 0)";
        }

        $revenue = $eventQuery->selectRaw($amountExpression . ' as total_revenue')->first()->total_revenue ?? 0.00;

        // Funnel representation
        $funnel = [
            ['stage' => 'Sent', 'value' => $sent, 'percentage' => '100%'],
            ['stage' => 'Delivered', 'value' => $delivered, 'percentage' => $sent > 0 ? round(($delivered / $sent) * 100, 1) . '%' : '0%'],
            ['stage' => 'Opened', 'value' => $opened, 'percentage' => $sent > 0 ? round(($opened / $sent) * 100, 1) . '%' : '0%'],
            ['stage' => 'Clicked', 'value' => $clicked, 'percentage' => $sent > 0 ? round(($clicked / $sent) * 100, 1) . '%' : '0%'],
            ['stage' => 'Converted', 'value' => $converted, 'percentage' => $sent > 0 ? round(($converted / $sent) * 100, 1) . '%' : '0%'],
        ];

        // Daily Trends (last 14 days)
        $dailyTrends = $this->getDailyTrends($campaignId);

        // Top Campaigns
        $topCampaigns = [];
        if (!$campaignId) {
            $topCampaigns = $this->getTopCampaigns($amountExpression);
        }

        return [
            'total_sent' => $sent,
            'total_delivered' => $delivered,
            'total_opened' => $opened,
            'total_clicked' => $clicked,
            'total_converted' => $converted,
            'conversion_rate' => $conversionRate,
            'open_rate' => $openRate,
            'ctr_rate' => $ctrRate,
            'revenue_generated' => round($revenue, 2),
            'campaign_funnel' => $funnel,
            'daily_trends' => $dailyTrends,
            'top_campaigns' => $topCampaigns,
            'total_customers' => \App\Models\Customer::count(),
            'active_campaigns' => \App\Models\Campaign::where('status', 'running')->count(),
        ];
    }

    private function getDailyTrends(?int $campaignId): array
    {
        $trends = [];
        $startDate = Carbon::now()->subDays(13)->startOfDay();

        $query = Communication::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as sent_count'),
                DB::raw("sum(case when status != 'failed' then 1 else 0 end) as delivered_count")
            )
            ->where('created_at', '>=', $startDate);

        if ($campaignId) {
            $query->where('campaign_id', $campaignId);
        }

        $rawTrends = $query->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        for ($i = 13; $i >= 0; $i--) {
            $dateString = Carbon::now()->subDays($i)->format('Y-m-d');
            $dayLabel = 'D' . (14 - $i);
            $trendData = $rawTrends->get($dateString);

            $trends[] = [
                'day' => $dayLabel,
                'date' => $dateString,
                'sent' => $trendData->sent_count ?? 0,
                'delivered' => $trendData->delivered_count ?? 0,
            ];
        }

        return $trends;
    }

    private function getTopCampaigns(string $amountExpression): array
    {
        return Campaign::select('campaigns.id', 'campaigns.name')
            ->selectRaw('count(communications.id) as sent')
            ->selectRaw("sum(case when communications.status = 'converted' then 1 else 0 end) as conversion")
            ->selectRaw($amountExpression . ' as revenue')
            ->leftJoin('communications', 'communications.campaign_id', '=', 'campaigns.id')
            ->leftJoin('campaign_events', function($join) {
                $join->on('campaign_events.communication_id', '=', 'communications.id')
                     ->where('campaign_events.event_type', '=', 'converted');
            })
            ->groupBy('campaigns.id', 'campaigns.name')
            ->orderBy('conversion', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($camp) {
                return [
                    'name' => $camp->name,
                    'sent' => intval($camp->sent),
                    'conversion' => intval($camp->conversion),
                    'revenue' => round(floatval($camp->revenue), 2),
                ];
            })
            ->toArray();
    }
}
