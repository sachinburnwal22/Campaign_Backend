<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    protected ReceiptService $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    /**
     * Webhook callback receiver endpoint.
     */
    public function receive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'communication_id' => 'required|integer|exists:communications,id',
            'status' => 'required|string|in:delivered,opened,read,clicked,failed,converted',
        ]);

        $this->receiptService->processReceipt(
            $validated['communication_id'],
            $validated['status']
        );

        return response()->json([
            'success' => true,
            'message' => 'Receipt processed successfully',
        ]);
    }
}
