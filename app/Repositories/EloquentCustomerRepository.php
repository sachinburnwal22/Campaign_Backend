<?php

namespace App\Repositories;

use App\Models\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentCustomerRepository implements CustomerRepositoryInterface
{
    public function paginate(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = Customer::query();

        $like = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        if (!empty($filters['name'])) {
            $query->where('name', $like, '%' . $filters['name'] . '%');
        }

        if (!empty($filters['city'])) {
            $query->where('city', $like, '%' . $filters['city'] . '%');
        }

        if (!empty($filters['email'])) {
            $query->where('email', $like, '%' . $filters['email'] . '%');
        }

        if (isset($filters['min_spent'])) {
            $query->where('total_spent', '>=', $filters['min_spent']);
        }

        if (isset($filters['max_spent'])) {
            $query->where('total_spent', '<=', $filters['max_spent']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Customer
    {
        return Customer::find($id);
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(int $id, array $data): ?Customer
    {
        $customer = $this->find($id);
        if ($customer) {
            $customer->update($data);
            return $customer;
        }
        return null;
    }

    public function delete(int $id): bool
    {
        $customer = $this->find($id);
        if ($customer) {
            return $customer->delete();
        }
        return false;
    }
}
