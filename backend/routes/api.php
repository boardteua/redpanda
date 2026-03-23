<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatArchiveController;
use App\Http\Controllers\Api\V1\ChatEmoticonController;
use App\Http\Controllers\Api\V1\ChatImageController;
use App\Http\Controllers\Api\V1\ChatMessageController;
use App\Http\Controllers\Api\V1\ChatSettingsController;
use App\Http\Controllers\Api\V1\FriendController;
use App\Http\Controllers\Api\V1\IgnoreController;
use App\Http\Controllers\Api\V1\LandingController;
use App\Http\Controllers\Api\V1\MeAccountController;
use App\Http\Controllers\Api\V1\MeProfileController;
use App\Http\Controllers\Api\V1\Mod\ChatEmoticonAdminController;
use App\Http\Controllers\Api\V1\ModerationController;
use App\Http\Controllers\Api\V1\OEmbedController;
use App\Http\Controllers\Api\V1\PrivateMessageController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\RoomPeerHintsController;
use App\Http\Controllers\Api\V1\RoomPresenceStatusController;
use App\Http\Controllers\Api\V1\RoomReadController;
use App\Http\Controllers\Api\V1\StaffUserController;
use App\Http\Controllers\Api\V1\UserAvatarController;
use App\Http\Controllers\Api\V1\UserLookupController;
use App\Http\Middleware\RejectBannedIp;
use App\Http\Middleware\RejectDisabledAccount;
use App\Http\Middleware\ResolveAuth0BearerUser;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware([RejectBannedIp::class])->group(function (): void {
    Route::middleware('throttle:auth-register')->post('auth/register', [AuthController::class, 'register']);
    Route::middleware('throttle:auth-login')->post('auth/login', [AuthController::class, 'login']);
    Route::middleware('throttle:auth-guest')->post('auth/guest', [AuthController::class, 'guest']);

    Route::middleware('throttle:landing-read')->get('landing', [LandingController::class, 'show']);

    Route::middleware([ResolveAuth0BearerUser::class, 'auth:sanctum', RejectDisabledAccount::class])->group(function (): void {
        Route::get('auth/user', [AuthController::class, 'user']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        Route::middleware('throttle:image-upload')->post('me/avatar', [UserAvatarController::class, 'store']);

        Route::middleware('throttle:me-profile')->group(function (): void {
            Route::get('me/profile', [MeProfileController::class, 'show']);
            Route::patch('me/profile', [MeProfileController::class, 'update']);
        });

        Route::middleware('throttle:me-account')->patch('me/account', [MeAccountController::class, 'update']);

        Route::middleware('throttle:chat-read')->group(function (): void {
            Route::get('rooms', [RoomController::class, 'index']);
            Route::get('rooms/{room}/messages', [ChatMessageController::class, 'index']);
            Route::get('chat/settings', [ChatSettingsController::class, 'show']);
            Route::middleware('throttle:emoticon-read')->get('chat/emoticons', [ChatEmoticonController::class, 'index']);
        });

        Route::middleware('throttle:chat-mark-read')->post(
            'rooms/{room}/read',
            [RoomReadController::class, 'store'],
        );

        Route::middleware('throttle:chat-read')->get(
            'rooms/{room}/peer-hints',
            [RoomPeerHintsController::class, 'index'],
        );

        Route::middleware('throttle:chat-read')->get(
            'rooms/{room}/presence-statuses',
            [RoomPresenceStatusController::class, 'index'],
        );
        Route::middleware('throttle:presence-status')->post(
            'rooms/{room}/presence-status',
            [RoomPresenceStatusController::class, 'store'],
        );

        Route::middleware(['can:chat-admin', 'throttle:mod-actions'])->patch('chat/settings', [ChatSettingsController::class, 'update']);

        Route::middleware('throttle:archive-read')->get('archive/messages', [ChatArchiveController::class, 'index']);

        Route::middleware('throttle:room-create')->post('rooms', [RoomController::class, 'store']);
        Route::middleware('throttle:room-manage')->patch('rooms/{room}', [RoomController::class, 'update']);
        Route::middleware('throttle:room-manage')->delete('rooms/{room}', [RoomController::class, 'destroy']);

        Route::middleware('throttle:chat-post')->post('rooms/{room}/messages', [ChatMessageController::class, 'store']);
        Route::middleware('throttle:chat-post')->patch('rooms/{room}/messages/{message}', [ChatMessageController::class, 'update']);
        Route::middleware('throttle:chat-post')->delete('rooms/{room}/messages/{message}', [ChatMessageController::class, 'destroy']);

        Route::middleware('throttle:image-read')->get('images', [ChatImageController::class, 'index']);
        Route::middleware('throttle:image-upload')->post('images', [ChatImageController::class, 'store']);
        Route::middleware('throttle:image-read')->get('images/{image}/file', [ChatImageController::class, 'file'])
            ->name('api.v1.chat-images.file');

        Route::middleware('throttle:user-autocomplete')->get('users/autocomplete', [UserLookupController::class, 'autocomplete']);
        Route::get('users/lookup', [UserLookupController::class, 'show']);

        Route::middleware('throttle:oembed-read')->get('oembed', [OEmbedController::class, 'show']);

        Route::middleware('throttle:private-read')->group(function (): void {
            Route::get('private/conversations', [PrivateMessageController::class, 'conversations']);
            Route::get('private/peers/{peer}/messages', [PrivateMessageController::class, 'index']);
            Route::post('private/peers/{peer}/read', [PrivateMessageController::class, 'read']);
        });

        Route::middleware('throttle:private-post')->post('private/peers/{peer}/messages', [PrivateMessageController::class, 'store']);
        Route::middleware('throttle:private-post')->delete('private/peers/{peer}/thread', [PrivateMessageController::class, 'destroyThread']);

        Route::get('friends', [FriendController::class, 'index']);
        Route::get('friends/requests/incoming', [FriendController::class, 'incoming']);
        Route::get('friends/requests/outgoing', [FriendController::class, 'outgoing']);
        Route::post('friends/{user}/accept', [FriendController::class, 'accept']);
        Route::post('friends/{user}/reject', [FriendController::class, 'reject']);
        Route::middleware('throttle:private-post')->delete('friends/{user}', [FriendController::class, 'destroy']);
        Route::post('friends/{user}', [FriendController::class, 'store']);

        Route::get('ignores', [IgnoreController::class, 'index']);
        Route::post('ignores/{user}', [IgnoreController::class, 'store']);
        Route::delete('ignores/{user}', [IgnoreController::class, 'destroy']);

        Route::middleware(['can:chat-admin', 'throttle:mod-user-read'])->prefix('mod')->group(function (): void {
            Route::get('users', [StaffUserController::class, 'index']);
            Route::get('users/{user}', [StaffUserController::class, 'show']);
        });

        Route::middleware(['can:chat-admin', 'throttle:mod-actions'])->prefix('mod')->group(function (): void {
            Route::get('emoticons', [ChatEmoticonAdminController::class, 'index']);
            Route::post('emoticons', [ChatEmoticonAdminController::class, 'store']);
            Route::patch('emoticons/{emoticon}', [ChatEmoticonAdminController::class, 'update']);
            Route::delete('emoticons/{emoticon}', [ChatEmoticonAdminController::class, 'destroy']);
            Route::get('banned-ips', [ModerationController::class, 'indexBannedIps']);
            Route::post('banned-ips', [ModerationController::class, 'storeBannedIp']);
            Route::delete('banned-ips/{bannedIp}', [ModerationController::class, 'destroyBannedIp']);
            Route::post('users/bulk', [StaffUserController::class, 'bulk']);
            Route::post('users', [StaffUserController::class, 'store']);
            Route::patch('users/{user}/profile', [StaffUserController::class, 'updateProfile']);
            Route::patch('users/{user}', [StaffUserController::class, 'update']);
        });

        Route::middleware(['can:moderate', 'throttle:mod-flagged-read'])->prefix('mod')->group(function (): void {
            Route::get('flagged-messages', [ModerationController::class, 'indexFlaggedMessages']);
        });

        Route::middleware(['can:moderate', 'throttle:mod-actions'])->prefix('mod')->group(function (): void {
            Route::patch('flagged-messages/{message}', [ModerationController::class, 'clearModerationFlag']);
            Route::get('filter-words', [ModerationController::class, 'indexFilterWords']);
            Route::post('filter-words', [ModerationController::class, 'storeFilterWord']);
            Route::patch('filter-words/{filterWord}', [ModerationController::class, 'updateFilterWord']);
            Route::delete('filter-words/{filterWord}', [ModerationController::class, 'destroyFilterWord']);
            Route::post('users/{user}/mute', [ModerationController::class, 'muteUser']);
            Route::post('users/{user}/kick', [ModerationController::class, 'kickUser']);
        });
    });
});
