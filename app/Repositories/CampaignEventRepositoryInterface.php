<?php

namespace App\Repositories;

use App\Models\CampaignEvent;
use Illuminate\Database\Eloquent\Collection;

interface CampaignEventRepositoryInterface
{
    public function create(array $data): CampaignEvent;
    public function getLatest(int $limit = 50): Collection;
}
