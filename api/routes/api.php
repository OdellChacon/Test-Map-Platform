<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);

        Route::get('reports', [ReportController::class, 'index']);
        Route::post('reports', [ReportController::class, 'store'])->middleware('throttle:reports');
        Route::patch('reports/{report}', [ReportController::class, 'update']);
        Route::post('reports/{report}/close', [ReportController::class, 'close']);
    });
});
