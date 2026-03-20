<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatMessageController;
use App\Http\Controllers\Api\V1\FriendController;
use App\Http\Controllers\Api\V1\IgnoreController;
use App\Http\Controllers\Api\V1\PrivateMessageController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\UserLookupController;
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

        Route::get('users/lookup', [UserLookupController::class, 'show']);

        Route::middleware('throttle:private-read')->group(function (): void {
            Route::get('private/conversations', [PrivateMessageController::class, 'conversations']);
            Route::get('private/peers/{peer}/messages', [PrivateMessageController::class, 'index']);
        });

        Route::middleware('throttle:private-post')->post('private/peers/{peer}/messages', [PrivateMessageController::class, 'store']);

        Route::get('friends', [FriendController::class, 'index']);
        Route::get('friends/requests/incoming', [FriendController::class, 'incoming']);
        Route::get('friends/requests/outgoing', [FriendController::class, 'outgoing']);
        Route::post('friends/{user}/accept', [FriendController::class, 'accept']);
        Route::post('friends/{user}/reject', [FriendController::class, 'reject']);
        Route::post('friends/{user}', [FriendController::class, 'store']);

        Route::get('ignores', [IgnoreController::class, 'index']);
        Route::post('ignores/{user}', [IgnoreController::class, 'store']);
        Route::delete('ignores/{user}', [IgnoreController::class, 'destroy']);
    });
});
