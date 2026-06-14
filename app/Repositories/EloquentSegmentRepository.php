<?php

namespace App\Repositories;

use App\Models\Segment;
use Illuminate\Database\Eloquent\Collection;

class EloquentSegmentRepository implements SegmentRepositoryInterface
{
    public function all(): Collection
    {
        return Segment::orderBy('id', 'desc')->get();
    }

    public function find(int $id): ?Segment
    {
        return Segment::find($id);
    }

    public function create(array $data): Segment
    {
        return Segment::create($data);
    }

    public function update(int $id, array $data): ?Segment
    {
        $segment = $this->find($id);
        if ($segment) {
            $segment->update($data);
            return $segment;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $segment = $this->find($id);
        if ($segment) {
            return $segment->delete();
        }
        return false;
    }
}
