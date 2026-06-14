<?php

namespace App\Services;

use App\Events\OrderCreated;
use App\Models\CampaignEvent;
use App\Models\Communication;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiptService
{
    /**
     * Process simulated receipt webhook callback.
     */
    public function processReceipt(int $communicationId, string $status): void
    {
        DB::transaction(function () use ($communicationId, $status) {
            $communication = Communication::lockForUpdate()->find($communicationId);

            if (!$communication) {
                Log::warning("Communication #{$communicationId} not found in receipt callback.");
                return;
            }

            // Update communication status
            $communication->update(['status' => $status]);

            $eventDetails = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'channel' => $communication->channel,
            ];

            // Handle conversions
            if ($status === 'converted') {
                $customer = $communication->customer;
                $campaign = $communication->campaign;

                // Create a realistic order
                $categories = ['Electronics', 'Apparel', 'Beauty', 'Home', 'Groceries'];
                $category = $categories[array_rand($categories)];
                $amount = rand(800, 6500) + (rand(0, 99) / 100);

                $order = Order::create([
                    'customer_id' => $customer->id,
                    'amount' => $amount,
                    'category' => $category,
                    'status' => 'completed',
                    'order_date' => Carbon::now(),
                ]);

                // Fire OrderCreated event to update customer total spent and last order date
                event(new OrderCreated($order));

                Log::info("Customer #{$customer->id} converted from Campaign #{$campaign->id}. Order #{$order->id} placed for ₹{$amount}.");

                $eventDetails['order_id'] = $order->id;
                $eventDetails['amount'] = $amount;
            }

            // Create campaign event log
            CampaignEvent::create([
                'communication_id' => $communicationId,
                'event_type' => $status,
                'details' => $eventDetails,
            ]);
        });
    }
}
