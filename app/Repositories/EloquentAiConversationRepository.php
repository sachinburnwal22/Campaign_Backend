<?php

namespace App\Repositories;

use App\Models\AiConversation;
use Illuminate\Database\Eloquent\Collection;

class EloquentAiConversationRepository implements AiConversationRepositoryInterface
{
    public function all(): Collection
    {
        return AiConversation::orderBy('id', 'desc')->get();
    }

    public function create(array $data): AiConversation
    {
        return AiConversation::create([
            'prompt' => $data['prompt'],
            'response_json' => $data['response_json'],
            'user_id' => $data['user_id'] ?? null,
        ]);
    }
}
