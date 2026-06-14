<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSegmentRequest;
use App\Repositories\SegmentRepositoryInterface;
use App\Services\SegmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    protected SegmentRepositoryInterface $segmentRepository;
    protected SegmentService $segmentService;

    public function __construct(SegmentRepositoryInterface $segmentRepository, SegmentService $segmentService)
    {
        $this->segmentRepository = $segmentRepository;
        $this->segmentService = $segmentService;
    }

    public function index(): JsonResponse
    {
        $segments = $this->segmentRepository->all();

        // Dynamically compute size (matched customer counts) for UI
        $segments->map(function ($segment) {
            $segment->matched_count = $this->segmentService->getCustomersCount($segment->rules_json);
            return $segment;
        });

        return response()->json($segments);
    }

    public function store(StoreSegmentRequest $request): JsonResponse
    {
        $segment = $this->segmentRepository->create($request->validated());
        $segment->matched_count = $this->segmentService->getCustomersCount($segment->rules_json);

        return response()->json($segment, 201);
    }

    public function show(int $id): JsonResponse
    {
        $segment = $this->segmentRepository->find($id);

        if (!$segment) {
            return response()->json(['message' => 'Segment not found'], 404);
        }

        $segment->matched_count = $this->segmentService->getCustomersCount($segment->rules_json);

        return response()->json($segment);
    }

    public function update(StoreSegmentRequest $request, int $id): JsonResponse
    {
        $segment = $this->segmentRepository->update($id, $request->validated());

        if (!$segment) {
            return response()->json(['message' => 'Segment not found'], 404);
        }

        $segment->matched_count = $this->segmentService->getCustomersCount($segment->rules_json);

        return response()->json($segment);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->segmentRepository->delete($id);

        if (!$deleted) {
            return response()->json(['message' => 'Segment not found'], 404);
        }

        return response()->json(['message' => 'Segment deleted successfully']);
    }

    /**
     * Get customers matching segment rules.
     */
    public function customers(Request $request, int $id): JsonResponse
    {
        $segment = $this->segmentRepository->find($id);

        if (!$segment) {
            return response()->json(['message' => 'Segment not found'], 404);
        }

        $perPage = $request->query('per_page', 15);
        $customers = $this->segmentService->getCustomersQuery($segment->rules_json)
            ->paginate(intval($perPage));

        return response()->json($customers);
    }
}
