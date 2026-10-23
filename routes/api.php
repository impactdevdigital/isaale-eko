<?php

use App\Http\Controllers\SquareWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhooks/square', SquareWebhookController::class)->name('webhooks.square');
