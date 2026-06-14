<?php

use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\CopilotController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\SegmentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// ShopReach CRM Core API Endpoints
Route::apiResource('customers', CustomerController::class);
Route::apiResource('orders', OrderController::class);

Route::apiResource('segments', SegmentController::class);
Route::get('segments/{segment}/customers', [SegmentController::class, 'customers']);

Route::apiResource('campaigns', CampaignController::class);
Route::post('campaigns/{campaign}/launch', [CampaignController::class, 'launch']);

// AI Copilot Endpoints (aliased for compatibility)
Route::post('copilot/chat', [CopilotController::class, 'chat']);
Route::post('chat', [CopilotController::class, 'chat']);

// Simulated Webhook Callback Receiver
Route::post('receipts', [ReceiptController::class, 'receive']);

// Live Activity Feed
Route::get('stream', [EventController::class, 'stream']);

// Analytics Engine
Route::get('analytics/overview', [AnalyticsController::class, 'overview']);
Route::get('analytics/campaign/{campaign}', [AnalyticsController::class, 'campaign']);
