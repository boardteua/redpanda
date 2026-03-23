<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChatSetting;
use App\Services\Chat\ChatOnlineSessionCounter;
use Illuminate\Http\JsonResponse;

class LandingController extends Controller
{
    /**
     * Публічний зріз вітальні (**T75** / **T77**): контент без авторизації, без PII.
     */
    public function show(): JsonResponse
    {
        $row = ChatSetting::current();

        return response()->json([
            'data' => [
                'landing' => $row->resolvedLandingSettings(),
                'registration' => $row->resolvedRegistrationFlags(),
                'users_online' => ChatOnlineSessionCounter::recentDistinctUserCount(),
                'auth0' => [
                    'enabled' => (bool) config('auth0.enabled'),
                    'domain' => config('auth0.enabled') ? config('auth0.domain') : null,
                    'client_id' => config('auth0.enabled') ? config('auth0.spa_client_id') : null,
                    'audience' => config('auth0.enabled') ? config('auth0.audience') : null,
                ],
            ],
        ]);
    }
}
