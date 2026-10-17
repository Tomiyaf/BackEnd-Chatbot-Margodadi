<?php

use App\Http\Controllers\Api\Admin\ActivityLogController;
use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\ConversationController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\EducationController;
use App\Http\Controllers\Api\Admin\KnowledgeBaseController;
use App\Http\Controllers\Api\Admin\OperatorController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\UmkmController as AdminUmkmController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\PublicServiceController as DirectPublicServiceController;
use App\Http\Controllers\Api\UmkmController as DirectUmkmController;
use App\Http\Controllers\Api\Public\ChatbotController;
use App\Http\Controllers\Api\Public\PublicServiceController;
use App\Http\Controllers\Api\Public\UmkmController;
use App\Http\Controllers\Api\Public\WahaWebhookController;
use Illuminate\Support\Facades\Route;

// WhatsApp WAHA Webhook (Incoming Messages)
Route::post('/webhook/waha', [WahaWebhookController::class, 'handleWebhook']);

// Global Search (Homepage Search Bar)
Route::get('/search', [GlobalSearchController::class, 'search']);

// Public Services Routes (Guest / Citizen Access)
Route::prefix('public-services')->group(function () {
    Route::get('/', [DirectPublicServiceController::class, 'index']);
    Route::get('/categories', [DirectPublicServiceController::class, 'categories']);
    Route::get('/{slug}', [DirectPublicServiceController::class, 'show']);
});

// UMKM Potensi Desa Routes (Guest / Citizen Access)
Route::prefix('umkms')->group(function () {
    Route::get('/', [DirectUmkmController::class, 'index']);
    Route::get('/categories', [DirectUmkmController::class, 'categories']);
    Route::get('/{id}', [DirectUmkmController::class, 'show']);
});

// Operator Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected Auth Routes
    Route::middleware(['auth:sanctum', 'operator.activity'])->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/heartbeat', [AuthController::class, 'heartbeat']);
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
Route::middleware(['auth:sanctum', 'operator.activity'])->prefix('admin')->group(function () {
    // Executive Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Conversations & HITL Management
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::get('/conversations/{id}', [ConversationController::class, 'show']);
    Route::post('/conversations/{id}/reply', [ConversationController::class, 'reply']);
    Route::patch('/conversations/{id}/status', [ConversationController::class, 'updateStatus']);
    Route::patch('/conversations/{id}/mode', [ConversationController::class, 'toggleInterventionMode']);
    Route::post('/conversations/{id}/assign', [ConversationController::class, 'assign']);

    // Operator Management
    Route::get('/operators', [OperatorController::class, 'index']);
    Route::patch('/operators/{id}/status', [OperatorController::class, 'updateStatus']);

    // Waste Education Monitoring
    Route::get('/education/sessions', [EducationController::class, 'index']);
    Route::get('/education/topics', [EducationController::class, 'topics']);

    // Analytics & SLA Performance
    Route::get('/analytics/overview', [AnalyticsController::class, 'overview']);

    // Knowledge Base & AI Vector Store Management
    Route::prefix('knowledge-base')->group(function () {
        Route::get('/stats', [KnowledgeBaseController::class, 'stats']);
        Route::get('/documents', [KnowledgeBaseController::class, 'index']);
        Route::post('/documents', [KnowledgeBaseController::class, 'store']);
        Route::get('/documents/{id}', [KnowledgeBaseController::class, 'show']);
        Route::put('/documents/{id}', [KnowledgeBaseController::class, 'update']);
        Route::delete('/documents/{id}', [KnowledgeBaseController::class, 'destroy']);
        Route::post('/documents/{id}/reindex', [KnowledgeBaseController::class, 'reindex']);
        Route::post('/reindex-all', [KnowledgeBaseController::class, 'reindexAll']);
        Route::post('/test-retrieval', [KnowledgeBaseController::class, 'testRetrieval']);
        Route::put('/chunks/{chunkId}', [KnowledgeBaseController::class, 'updateChunk']);
        Route::delete('/chunks/{chunkId}', [KnowledgeBaseController::class, 'deleteChunk']);
    });

    // Education Data Export
    Route::get('/education/export-csv', [EducationController::class, 'exportCsv']);

    // UMKM & Potensi Desa Back-Office Management (Admin & Operator Access)
    Route::prefix('umkms')->group(function () {
        Route::get('/stats', [AdminUmkmController::class, 'stats']);
        Route::get('/categories', [AdminUmkmController::class, 'categories']);
        Route::post('/sync-all-to-rag', [AdminUmkmController::class, 'syncAllToRag']);
        Route::get('/', [AdminUmkmController::class, 'index']);
        Route::post('/', [AdminUmkmController::class, 'store']);
        Route::get('/{id}', [AdminUmkmController::class, 'show']);
        Route::post('/{id}', [AdminUmkmController::class, 'update']);
        Route::put('/{id}', [AdminUmkmController::class, 'update']);
        Route::delete('/{id}', [AdminUmkmController::class, 'destroy']);
        Route::patch('/{id}/status', [AdminUmkmController::class, 'updateStatus']);
        Route::patch('/{id}/verify', [AdminUmkmController::class, 'verify']);
        Route::post('/{id}/sync-ai', [AdminUmkmController::class, 'syncToRag']);
    });

    // Activity Logs
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);

    // Settings & Configuration
    Route::get('/settings/rag', [SettingController::class, 'getRag']);
    Route::put('/settings/rag', [SettingController::class, 'updateRag']);
    Route::put('/settings/profile', [SettingController::class, 'updateProfile']);
    Route::post('/settings/change-password', [SettingController::class, 'changePassword']);
});

