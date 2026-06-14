<?php

namespace App\Jobs;

use App\Repositories\AiConversationRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogAiConversationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $prompt;
    protected array $responseJson;
    protected ?int $userId;

    /**
     * Create a new job instance.
     */
    public function __construct(string $prompt, array $responseJson, ?int $userId = null)
    {
        $this->prompt = $prompt;
        $this->responseJson = $responseJson;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(AiConversationRepositoryInterface $repository): void
    {
        Log::info("Logging AI Conversation in background.");

        try {
            $repository->create([
                'prompt' => $this->prompt,
                'response_json' => $this->responseJson,
                'user_id' => $this->userId,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to log AI Conversation: " . $e->getMessage());
        }
    }
}
