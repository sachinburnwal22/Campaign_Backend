<?php

namespace Database\Seeders;

use App\Models\Segment;
use Illuminate\Database\Seeder;

class SegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            [
                'name' => 'High-Value Dormant',
                'description' => 'Spent ₹5k+ · inactive 45+ days',
                'rules_json' => [
                    'total_spent' => 5000,
                    'inactive_days' => 45
                ],
            ],
            [
                'name' => 'Mumbai Active Buyers',
                'description' => 'Mumbai based · ordered in last 30 days',
                'rules_json' => [
                    'city' => 'Mumbai',
                    'last_order_days' => 30
                ],
            ],
            [
                'name' => 'VIP Loyalists',
                'description' => 'Top spenders with multiple purchases',
                'rules_json' => [
                    'total_spent' => 10000,
                    'order_count' => 5
                ],
            ],
            [
                'name' => 'Delhi Dormant buyers',
                'description' => 'Delhi residents · inactive for 60+ days',
                'rules_json' => [
                    'city' => 'Delhi',
                    'inactive_days' => 60
                ],
            ],
            [
                'name' => 'Female Shoppers Bengaluru',
                'description' => 'Female shoppers located in Bengaluru',
                'rules_json' => [
                    'gender' => 'Female',
                    'city' => 'Bengaluru'
                ],
            ]
        ];

        foreach ($segments as $segment) {
            Segment::create($segment);
        }
    }
}
