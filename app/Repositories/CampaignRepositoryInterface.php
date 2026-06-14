<?php

namespace App\Repositories;

use App\Models\Campaign;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CampaignRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): ?Campaign;
    public function create(array $data): Campaign;
    public function update(int $id, array $data): ?Campaign;
    public function delete(int $id): bool;
}
