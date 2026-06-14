<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCampaignRepository implements CampaignRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Campaign::with('segment')->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Campaign
    {
        return Campaign::with(['segment', 'communications'])->find($id);
    }

    public function create(array $data): Campaign
    {
        return Campaign::create($data);
    }

    public function update(int $id, array $data): ?Campaign
    {
        $campaign = $this->find($id);
        if ($campaign) {
            $campaign->update($data);
            return $campaign;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $campaign = $this->find($id);
        if ($campaign) {
            return $campaign->delete();
        }
        return false;
    }
}
