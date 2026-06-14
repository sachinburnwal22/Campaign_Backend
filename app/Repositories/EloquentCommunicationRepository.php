<?php

namespace App\Repositories;

use App\Models\Communication;

class EloquentCommunicationRepository implements CommunicationRepositoryInterface
{
    public function create(array $data): Communication
    {
        return Communication::create($data);
    }

    public function update(int $id, array $data): ?Communication
    {
        $communication = $this->find($id);
        if ($communication) {
            $communication->update($data);
            return $communication;
        }
        return null;
    }

    public function find(int $id): ?Communication
    {
        return Communication::with(['campaign', 'customer'])->find($id);
    }
}
