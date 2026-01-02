<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/midtrans/webhook', [\App\Http\Controllers\MidtransController::class, 'webhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index']);
    Route::get('/subscriptions/check/{orderId}', [\App\Http\Controllers\SubscriptionController::class, 'checkStatus']);
    Route::delete('/subscriptions/{id}', [\App\Http\Controllers\SubscriptionController::class, 'destroy']);
    Route::post('/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout']);
});
