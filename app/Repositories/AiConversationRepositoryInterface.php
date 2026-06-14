<?php

namespace App\Repositories;

use App\Models\AiConversation;
use Illuminate\Database\Eloquent\Collection;

interface AiConversationRepositoryInterface
{
    public function all(): Collection;
    public function create(array $data): AiConversation;
}
