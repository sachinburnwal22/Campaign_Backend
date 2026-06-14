<?php

namespace App\Repositories;

use App\Models\CampaignEvent;
use Illuminate\Database\Eloquent\Collection;

class EloquentCampaignEventRepository implements CampaignEventRepositoryInterface
{
    public function create(array $data): CampaignEvent
    {
        return CampaignEvent::create($data);
    }

    public function getLatest(int $limit = 50): Collection
    {
        return CampaignEvent::with(['communication.customer', 'communication.campaign'])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }
}
