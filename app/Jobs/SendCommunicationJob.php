<?php

namespace App\Jobs;

use App\Models\Communication;
use App\Services\ChannelService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCommunicationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $communicationId;

    public function __construct(int $communicationId)
    {
        $this->communicationId = $communicationId;
    }

    public function handle(): void
    {
        Log::info("Dispatching SendCommunicationJob for communication #{$this->communicationId}");

        $communication = Communication::with('customer')->find($this->communicationId);

        if (!$communication) {
            Log::warning("Communication #{$this->communicationId} not found in send job.");
            return;
        }

        // Send using simulated ChannelService
        try {
            app(ChannelService::class)->sendMessage(
                $communication->id,
                $communication->customer->toArray(),
                $communication->message,
                $communication->channel
            );
        } catch (\Exception $e) {
            Log::error("Failed to send communication #{$this->communicationId}: " . $e->getMessage());
        }
    }
}
