<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ModerationController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PublicReportController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');
    Route::get('public/reports', [PublicReportController::class, 'index'])->middleware('throttle:public');
    Route::get('public/stats', [StatsController::class, 'index'])->middleware('throttle:public');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::get('reports', [ReportController::class, 'index']);
        Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:reports');
        Route::patch('reports/{report}', [ReportController::class, 'update'])->middleware('throttle:report-mutations');
        Route::post('reports/{report}/close', [ReportController::class, 'close'])->middleware('throttle:report-mutations');
        Route::post('reports/{report}/confirm', [ReportController::class, 'confirm'])->middleware('throttle:confirmations');

        Route::get('subscriptions', [SubscriptionController::class, 'index']);
        Route::post('subscriptions', [SubscriptionController::class, 'store']);
        Route::delete('subscriptions/{subscription}', [SubscriptionController::class, 'destroy']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);

        Route::get('moderation/reports', [ModerationController::class, 'index']);
        Route::post('moderation/reports/{report}', [ModerationController::class, 'store']);
    });
});
