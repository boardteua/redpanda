<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\Push\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PushSubscriptionController extends Controller
{
    public function __construct(
        private readonly WebPushService $webPush,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $this->registeredUser($request);

        if (! $this->webPush->isConfigured()) {
            return response()->json([
                'message' => 'Web Push ще не налаштовано на сервері.',
            ], 503);
        }

        $validated = $request->validate([
            'subscription' => ['required', 'array'],
            'subscription.endpoint' => ['required', 'url', 'max:2000'],
            'subscription.keys' => ['required', 'array'],
            'subscription.keys.p256dh' => ['required', 'string', 'max:255'],
            'subscription.keys.auth' => ['required', 'string', 'max:255'],
            'subscription.contentEncoding' => ['nullable', 'string', Rule::in(['aesgcm', 'aes128gcm'])],
        ]);

        $subscription = $validated['subscription'];
        $endpoint = trim((string) $subscription['endpoint']);
        $row = PushSubscription::query()->updateOrCreate(
            ['endpoint_hash' => PushSubscription::hashEndpoint($endpoint)],
            [
                'user_id' => $user->id,
                'endpoint' => $endpoint,
                'public_key' => (string) $subscription['keys']['p256dh'],
                'auth_token' => (string) $subscription['keys']['auth'],
                'content_encoding' => $subscription['contentEncoding'] ?? null,
            ],
        );

        return response()->json([
            'data' => [
                'id' => $row->id,
                'endpoint' => $row->endpoint,
                'content_encoding' => $row->content_encoding,
            ],
        ], $row->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $this->registeredUser($request);

        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2000'],
        ]);

        $removed = PushSubscription::query()
            ->where('user_id', $user->id)
            ->where('endpoint_hash', PushSubscription::hashEndpoint((string) $validated['endpoint']))
            ->delete();

        return response()->json([
            'meta' => [
                'ok' => true,
                'removed' => $removed,
            ],
        ]);
    }

    private function registeredUser(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        if ($user->guest) {
            abort(403, 'Web Push доступний лише для зареєстрованих користувачів.');
        }

        return $user;
    }
}
