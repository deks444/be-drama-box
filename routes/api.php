<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::post('/pakasir/webhook', [\App\Http\Controllers\PakasirController::class, 'webhook']);

// Admin routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [\App\Http\Controllers\AdminController::class, 'login']);

    // Protected admin routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\AdminController::class, 'logout']);
        Route::get('/stats', [\App\Http\Controllers\AdminController::class, 'getStats']);
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'getUsers']);
        Route::get('/users/search', [\App\Http\Controllers\AdminController::class, 'searchUsers']);
        Route::post('/users/grant-premium', [\App\Http\Controllers\AdminController::class, 'grantPremium']);
        Route::get('/transactions', [\App\Http\Controllers\AdminController::class, 'getTransactions']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/subscriptions', [\App\Http\Controllers\SubscriptionController::class, 'index']);
    Route::get('/subscriptions/check/{orderId}', [\App\Http\Controllers\SubscriptionController::class, 'checkStatus']);
    Route::delete('/subscriptions/{id}', [\App\Http\Controllers\SubscriptionController::class, 'destroy']);
    Route::post('/checkout', [\App\Http\Controllers\SubscriptionController::class, 'checkout']);
});
