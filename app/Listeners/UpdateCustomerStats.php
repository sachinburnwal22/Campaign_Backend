<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateCustomerStats implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        $customer = Customer::find($order->customer_id);

        if ($customer) {
            Log::info("Recalculating stats for customer #{$customer->id} due to order #{$order->id}");

            // Recalculate total spent and last order date from all orders
            $stats = $customer->orders()
                ->where('status', 'completed')
                ->selectRaw('COALESCE(SUM(amount), 0) as total_spent, MAX(order_date) as last_order_date')
                ->first();

            $customer->update([
                'total_spent' => $stats->total_spent ?? 0.00,
                'last_order_date' => $stats->last_order_date ?? null,
            ]);
        }
    }
}
