<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('customer')->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Order
    {
        return Order::with('customer')->find($id);
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function update(int $id, array $data): ?Order
    {
        $order = $this->find($id);
        if ($order) {
            $order->update($data);
            return $order;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $order = $this->find($id);
        if ($order) {
            return $order->delete();
        }
        return false;
    }
}
