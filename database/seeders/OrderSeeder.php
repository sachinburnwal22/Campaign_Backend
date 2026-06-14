<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customerIds = Customer::pluck('id')->toArray();
        if (empty($customerIds)) {
            return;
        }

        $categories = ['Electronics', 'Apparel', 'Beauty', 'Home', 'Groceries', 'Books', 'Sports'];
        $statuses = ['completed', 'completed', 'completed', 'completed', 'completed', 'refunded', 'cancelled'];

        $orders = [];

        for ($i = 0; $i < 2000; $i++) {
            $customerId = $customerIds[array_rand($customerIds)];
            $amount = rand(250, 8500) + (rand(0, 99) / 100);
            $category = $categories[array_rand($categories)];
            $status = $statuses[array_rand($statuses)];
            
            // Random date within the last 12 months
            $orderDate = Carbon::now()->subDays(rand(0, 365))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $orders[] = [
                'customer_id' => $customerId,
                'amount' => $amount,
                'category' => $category,
                'status' => $status,
                'order_date' => $orderDate,
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ];
        }

        // Chunk insert
        foreach (array_chunk($orders, 200) as $chunk) {
            Order::insert($chunk);
        }

        // Update customer total_spent and last_order_date aggregates
        $this->command->info('Recalculating customer spending aggregates...');
        
        $customerStats = Order::where('status', 'completed')
            ->select('customer_id', DB::raw('SUM(amount) as total_spent'), DB::raw('MAX(order_date) as last_order_date'))
            ->groupBy('customer_id')
            ->get();

        foreach ($customerStats as $stat) {
            Customer::where('id', $stat->customer_id)->update([
                'total_spent' => $stat->total_spent,
                'last_order_date' => $stat->last_order_date,
            ]);
        }
    }
}
