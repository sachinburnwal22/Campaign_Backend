<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default API user
        User::updateOrCreate(
            ['email' => 'aarav@brand.in'],
            [
                'name' => 'Aarav Sharma',
                'password' => bcrypt('password'),
            ]
        );

        $this->call([
            CustomerSeeder::class,
            OrderSeeder::class,
            SegmentSeeder::class,
            CampaignSeeder::class,
        ]);
    }
}
