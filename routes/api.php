<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Series search
    Route::get('/series/search', [SeriesController::class, 'search']);
    Route::get('/series/{tmdbId}', [SeriesController::class, 'show']);
    Route::get('/series/{tmdbId}/season/{season}', [SeriesController::class, 'season']);

    // Chat
    Route::post('/chat', [ChatController::class, 'ask']);
    Route::get('/conversations', [ChatController::class, 'conversations']);
    Route::get('/conversations/{id}', [ChatController::class, 'conversation']);

    // Subscriptions
    Route::get('/subscription/status', [SubscriptionController::class, 'status']);
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout']);
    Route::post('/subscription/portal', [SubscriptionController::class, 'portal']);
});
