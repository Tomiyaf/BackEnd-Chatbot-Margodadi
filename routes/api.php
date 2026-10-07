<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\ConversationController;
use App\Http\Controllers\Api\Admin\OperatorController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// Public Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// Protected Admin & Operator Routes
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Conversations & HITL Management
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations/{id}/reply', [ConversationController::class, 'reply']);
    Route::patch('/conversations/{id}/status', [ConversationController::class, 'updateStatus']);
    Route::post('/conversations/{id}/assign', [ConversationController::class, 'assign']);

    // Operator Management
    Route::get('/operators', [OperatorController::class, 'index']);
    Route::patch('/operators/{id}/status', [OperatorController::class, 'updateStatus']);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});
