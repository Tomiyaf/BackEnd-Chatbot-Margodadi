<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\ConversationController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EducationController;
use App\Http\Controllers\Api\Admin\OperatorController;
use App\Http\Controllers\Api\Admin\ResearchExportController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Public\ChatbotController;
use App\Http\Controllers\Api\Public\PublicServiceController;
use App\Http\Controllers\Api\Public\UmkmController;
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

// Public Citizen & Portal Routes
Route::prefix('public')->group(function () {
    // Public Services Directory
    Route::get('/services', [PublicServiceController::class, 'index']);
    Route::get('/services/{idOrSlug}', [PublicServiceController::class, 'show']);

    // UMKM Directory
    Route::get('/umkms', [UmkmController::class, 'index']);
    Route::get('/umkms/{id}', [UmkmController::class, 'show']);

    // Education Topics Directory
    Route::get('/education/topics', [EducationController::class, 'topics']);

    // Chatbot & Virtual Guide
    Route::post('/chatbot/init', [ChatbotController::class, 'initSession']);
    Route::get('/chatbot/sync', [ChatbotController::class, 'syncMessages']);
    Route::post('/chatbot/send', [ChatbotController::class, 'sendMessage']);
    Route::post('/chatbot/feedback', [ChatbotController::class, 'submitFeedback']);
});

// Protected Admin & Operator Routes
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    // Executive Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Conversations & HITL Management
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations/{id}/reply', [ConversationController::class, 'reply']);
    Route::patch('/conversations/{id}/status', [ConversationController::class, 'updateStatus']);
    Route::post('/conversations/{id}/assign', [ConversationController::class, 'assign']);

    // Operator Management
    Route::get('/operators', [OperatorController::class, 'index']);
    Route::patch('/operators/{id}/status', [OperatorController::class, 'updateStatus']);

    // Waste Education Monitoring
    Route::get('/education/sessions', [EducationController::class, 'index']);
    Route::get('/education/topics', [EducationController::class, 'topics']);

    // Analytics & SLA Performance
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);

    // Research Export (Thesis & PKM)
    Route::get('/research/preview', [ResearchExportController::class, 'preview']);
    Route::get('/research/export-csv', [ResearchExportController::class, 'exportCsv']);

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // Settings & Configuration
    Route::get('/settings/rag', [SettingController::class, 'getRag']);
    Route::put('/settings/rag', [SettingController::class, 'updateRag']);
    Route::put('/settings/profile', [SettingController::class, 'updateProfile']);
    Route::post('/settings/change-password', [SettingController::class, 'changePassword']);
});

