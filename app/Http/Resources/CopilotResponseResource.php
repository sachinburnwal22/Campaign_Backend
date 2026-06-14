<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CopilotResponseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'segment' => $this->resource['segment'],
            'audience' => $this->resource['audience'],
            'prediction' => $this->resource['prediction'],
            'channel' => $this->resource['channel'],
            'message' => $this->resource['message'],
            'campaign_draft' => $this->resource['campaign_draft'],
            // Included for backward compatibility with frontend
            'campaign' => $this->resource['campaign'] ?? null,
        ];
    }
}
