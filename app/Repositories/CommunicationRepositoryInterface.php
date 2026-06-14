<?php

namespace App\Repositories;

use App\Models\Communication;

interface CommunicationRepositoryInterface
{
    public function create(array $data): Communication;
    public function update(int $id, array $data): ?Communication;
    public function find(int $id): ?Communication;
}
