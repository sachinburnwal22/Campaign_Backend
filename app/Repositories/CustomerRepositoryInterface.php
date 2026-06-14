<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CustomerRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator;
    public function find(int $id): ?Customer;
    public function create(array $data): Customer;
    public function update(int $id, array $data): ?Customer;
    public function delete(int $id): bool;
}
