<?php

namespace App\Repositories;

use App\Models\Segment;
use Illuminate\Database\Eloquent\Collection;

interface SegmentRepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Segment;
    public function create(array $data): Segment;
    public function update(int $id, array $data): ?Segment;
    public function delete(int $id): bool;
}
