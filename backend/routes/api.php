<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatMessageController;
use App\Http\Controllers\Api\V1\RoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('throttle:auth-register')->post('auth/register', [AuthController::class, 'register']);
    Route::middleware('throttle:auth-login')->post('auth/login', [AuthController::class, 'login']);
    Route::middleware('throttle:auth-guest')->post('auth/guest', [AuthController::class, 'guest']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('auth/user', [AuthController::class, 'user']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::middleware('throttle:chat-read')->group(function (): void {
            Route::get('rooms', [RoomController::class, 'index']);
            Route::get('rooms/{room}/messages', [ChatMessageController::class, 'index']);
        });

        Route::middleware('throttle:chat-post')->post('rooms/{room}/messages', [ChatMessageController::class, 'store']);
    });
});
