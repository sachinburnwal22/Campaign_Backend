<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    public function find(int $id): ?Order;
    public function create(array $data): Order;
    public function update(int $id, array $data): ?Order;
    public function delete(int $id): bool;
}
