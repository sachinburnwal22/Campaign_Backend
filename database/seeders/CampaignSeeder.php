<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignEvent;
use App\Models\Communication;
use App\Models\Segment;
use App\Services\SegmentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $segments = Segment::all();
        if ($segments->isEmpty()) {
            return;
        }

        $channels = ['whatsapp', 'email', 'sms', 'rcs'];
        $statuses = ['completed', 'completed', 'completed', 'completed', 'running', 'draft'];
        
        $campaignTemplates = [
            'Diwali Loyalty Drop', 'Cart Recovery Flow', 'New Customer Welcome', 'Birthday Treats',
            'Flash Sale - 48hrs', 'Refer & Earn', 'Weekend Special Offer', 'Apparel Clearout',
            'VIP Sneak Peek', 'Re-engage Dormant Users', 'Bengaluru Shoppers Fest', 'Monsoon Magic Sale',
            'Delhi Winter Drop', 'Tech Enthusiasts Promo', 'Beauty Box Discount', 'Sports Gear Launch',
            'Home Decor Refresh', 'Groceries Stock Up Offer', 'Bookworms Sunday Read', 'App Store Launch Promo'
        ];

        $segmentService = app(SegmentService::class);

        for ($i = 0; $i < 20; $i++) {
            $segment = $segments->random();
            $channel = $channels[array_rand($channels)];
            $status = $statuses[array_rand($statuses)];
            $name = $campaignTemplates[$i];

            $campaign = Campaign::create([
                'name' => $name,
                'segment_id' => $segment->id,
                'channel' => $channel,
                'message' => "Hi {{first_name}}, enjoy an exclusive 20% discount on our store. Use code: DISCOUNT20. Shop now: {{store_link}}",
                'status' => $status,
                'expected_revenue' => rand(50000, 350000),
            ]);

            // If campaign is completed or running, seed historical communication & event logs
            if ($status === 'completed' || $status === 'running') {
                $this->command->info("Seeding log data for campaign: {$name}...");
                
                // Get segment customers
                $customers = $segmentService->getCustomers($segment->rules_json)->take(50); // limit to 50 for faster seed
                if ($customers->isEmpty()) {
                    continue;
                }

                $comms = [];
                $events = [];

                foreach ($customers as $customer) {
                    // Random date within the last 14 days
                    $date = Carbon::now()->subDays(rand(1, 14))->subHours(rand(0, 23));

                    // Random status distribution
                    $roll = rand(1, 100);
                    $commStatus = 'sent';
                    if ($roll <= 5) {
                        $commStatus = 'failed';
                    } elseif ($roll <= 35) {
                        $commStatus = 'delivered';
                    } elseif ($roll <= 65) {
                        $commStatus = 'opened';
                    } elseif ($roll <= 85) {
                        $commStatus = 'clicked';
                    } else {
                        $commStatus = 'converted';
                    }

                    // Create communication record
                    $comm = Communication::create([
                        'campaign_id' => $campaign->id,
                        'customer_id' => $customer->id,
                        'channel' => $channel,
                        'message' => "Hi {$customer->name}, enjoy an exclusive 20% discount on our store. Use code: DISCOUNT20. Shop now: {{store_link}}",
                        'status' => $commStatus,
                        'sent_at' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    // Generate historical events progressively
                    $events[] = [
                        'communication_id' => $comm->id,
                        'event_type' => 'sent',
                        'details' => json_encode(['timestamp' => $date->toIso8601String()]),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ];

                    if ($commStatus !== 'failed') {
                        $delDate = $date->copy()->addMinutes(rand(1, 5));
                        $events[] = [
                            'communication_id' => $comm->id,
                            'event_type' => 'delivered',
                            'details' => json_encode(['timestamp' => $delDate->toIso8601String()]),
                            'created_at' => $delDate,
                            'updated_at' => $delDate,
                        ];

                        if (in_array($commStatus, ['opened', 'clicked', 'converted'])) {
                            $opDate = $delDate->copy()->addMinutes(rand(5, 30));
                            $events[] = [
                                'communication_id' => $comm->id,
                                'event_type' => 'opened',
                                'details' => json_encode(['timestamp' => $opDate->toIso8601String()]),
                                'created_at' => $opDate,
                                'updated_at' => $opDate,
                            ];

                            if (in_array($commStatus, ['clicked', 'converted'])) {
                                $clkDate = $opDate->copy()->addMinutes(rand(2, 10));
                                $events[] = [
                                    'communication_id' => $comm->id,
                                    'event_type' => 'clicked',
                                    'details' => json_encode(['timestamp' => $clkDate->toIso8601String()]),
                                    'created_at' => $clkDate,
                                    'updated_at' => $clkDate,
                                ];

                                if ($commStatus === 'converted') {
                                    $convDate = $clkDate->copy()->addMinutes(rand(5, 20));
                                    $amount = rand(800, 6000) + (rand(0, 99) / 100);
                                    
                                    $events[] = [
                                        'communication_id' => $comm->id,
                                        'event_type' => 'converted',
                                        'details' => json_encode([
                                            'timestamp' => $convDate->toIso8601String(),
                                            'amount' => $amount,
                                            'order_id' => rand(1000, 9999)
                                        ]),
                                        'created_at' => $convDate,
                                        'updated_at' => $convDate,
                                    ];
                                }
                            }
                        }
                    } else {
                        // Failed event
                        $failDate = $date->copy()->addMinutes(rand(1, 2));
                        $events[] = [
                            'communication_id' => $comm->id,
                            'event_type' => 'failed',
                            'details' => json_encode(['timestamp' => $failDate->toIso8601String(), 'reason' => 'Network error']),
                            'created_at' => $failDate,
                            'updated_at' => $failDate,
                        ];
                    }
                }

                // Chunk insert events
                foreach (array_chunk($events, 200) as $chunk) {
                    CampaignEvent::insert($chunk);
                }
            }
        }
    }
}
