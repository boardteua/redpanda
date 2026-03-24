<?php

namespace App\Http\Controllers\Api\V1\Mod;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Moderation\UserNetworkInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ModUserNetworkInsightController extends Controller
{
    public function __construct(
        private readonly UserNetworkInsightService $networkInsight,
    ) {}

    public function show(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        if ($actor === null || $actor->guest || ! $actor->canModerate()) {
            abort(403);
        }

        $payload = $this->networkInsight->buildForUserId((int) $user->id);

        Log::info('staff.network_insight.viewed', [
            'actor_id' => (int) $actor->id,
            'subject_user_id' => (int) $user->id,
        ]);

        return response()->json(['data' => $payload]);
    }
}
