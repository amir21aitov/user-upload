<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('login', [AuthController::class, 'login']);
    });
});

Route::prefix('images')->middleware(['auth:api', 'jwt'])->group(function () {
    Route::get('/', [ImageController::class, 'index']);
    Route::post('upload', [ImageController::class, 'upload']);
    Route::get('/{image}', [ImageController::class, 'show']);
    Route::delete('/{image}', [ImageController::class, 'destroy']);
});
