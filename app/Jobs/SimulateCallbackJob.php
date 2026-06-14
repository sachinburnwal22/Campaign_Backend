<?php

namespace App\Jobs;

use App\Services\ReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SimulateCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $communicationId;
    protected string $status;

    public function __construct(int $communicationId, string $status)
    {
        $this->communicationId = $communicationId;
        $this->status = $status;
    }

    public function handle(): void
    {
        Log::info("Executing simulated callback for communication #{$this->communicationId} with status: {$this->status}");
        
        try {
            // Call the ReceiptService internally to simulate the webhook callback
            app(ReceiptService::class)->processReceipt($this->communicationId, $this->status);
        } catch (\Exception $e) {
            Log::error("Failed to process simulated callback: " . $e->getMessage());
        }
    }
}
