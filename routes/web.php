<?php

use App\Http\Controllers\Api\ChannelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Simulated external channel service
Route::post('/channel/send', [ChannelController::class, 'send'])->name('channel.send');
