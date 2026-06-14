<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Repositories\OrderRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->query('per_page', 15);
        $orders = $this->orderRepository->paginate(intval($perPage));

        return response()->json($orders);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderRepository->create($request->validated());

        // Dispatch OrderCreated event to trigger listeners updating customer aggregates
        event(new OrderCreated($order));

        return response()->json($order->load('customer'), 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    public function update(StoreOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderRepository->update($id, $request->validated());

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Fire event to re-calculate stats
        event(new OrderCreated($order));

        return response()->json($order->load('customer'));
    }

    public function destroy(int $id): JsonResponse
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $customerId = $order->customer_id;
        $deleted = $this->orderRepository->delete($id);

        if ($deleted) {
            // Trigger stats recalculation by creating a mock order with amount 0 to force run listener
            $dummyOrder = new \App\Models\Order(['customer_id' => $customerId]);
            event(new OrderCreated($dummyOrder));
        }

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
