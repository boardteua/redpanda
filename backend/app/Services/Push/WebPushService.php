<?php

namespace App\Services\Push;

use App\Models\ChatMessage;
use App\Models\PrivateMessage;
use App\Models\PushSubscription;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function __construct(
        private readonly WebPushPreferenceEvaluator $preferences,
    ) {}

    public function isConfigured(): bool
    {
        return $this->vapidSubject() !== null
            && $this->vapidPublicKey() !== null
            && $this->vapidPrivateKey() !== null;
    }

    public function publicKey(): ?string
    {
        return $this->vapidPublicKey();
    }

    public function sendRoomMessage(ChatMessage $message): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $message->loadMissing('room', 'user');
        if ($message->type !== 'public' || $message->room === null) {
            return;
        }

        $room = $message->room;
        $authorId = (int) $message->user_id;
        $subscriptions = PushSubscription::query()
            ->with('user:id,guest,vip,user_rank,web_push_master_enabled')
            ->where('user_id', '!=', $authorId)
            ->get()
            ->filter(fn (PushSubscription $subscription): bool => $this->userCanReceiveRoomPush($subscription->user, $room)
                && $this->preferences->shouldDeliverRoomWebPush($subscription->user, $room))
            ->values();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $author = $message->user;
        $authorAvatar = $author?->signedPublicAvatarUrlForPush();
        $fallbackIcon = url('/pwa/icon-192.png');

        $payload = [
            'title' => sprintf('%s у кімнаті %s', (string) $message->post_user, (string) $room->room_name),
            'body' => $this->messagePreview((string) $message->post_message, (int) $message->file > 0),
            'tag' => 'room-'.$message->post_id,
            'data' => [
                'kind' => 'room',
                'room_id' => (int) $room->room_id,
                'room_slug' => (string) ($room->slug ?? ''),
                'url' => $this->roomUrl($room),
            ],
            'author_avatar_url' => $authorAvatar,
            'icon' => $authorAvatar ?? $fallbackIcon,
            'badge' => url('/pwa/icon-96.png'),
        ];

        $this->sendPayload($subscriptions, $payload, 'room-'.$message->post_id);
    }

    public function sendPrivateMessage(PrivateMessage $message): void
    {
        if (! $this->isConfigured()) {
            return;
        }

        $message->loadMissing('sender', 'recipient');
        if ($message->recipient === null || $message->recipient->guest) {
            return;
        }

        if (! $this->preferences->shouldDeliverPrivateWebPush($message->recipient, (int) $message->sender_id)) {
            return;
        }

        $subscriptions = PushSubscription::query()
            ->where('user_id', $message->recipient_id)
            ->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $sender = $message->sender;
        $senderName = $sender?->user_name ?: 'Чат Рудої Панди';
        $authorAvatar = $sender?->signedPublicAvatarUrlForPush();
        $fallbackIcon = url('/pwa/icon-192.png');

        $hasImage = (int) ($message->image_id ?? 0) > 0;

        $payload = [
            'title' => sprintf('Приват від %s', $senderName),
            'body' => $this->messagePreview((string) $message->body, $hasImage),
            'tag' => 'private-'.$message->id,
            'data' => [
                'kind' => 'private',
                'peer_id' => (int) $message->sender_id,
                'peer_user_name' => $senderName,
                'url' => $this->privateThreadUrl((int) $message->sender_id, $senderName),
            ],
            'author_avatar_url' => $authorAvatar,
            'icon' => $authorAvatar ?? $fallbackIcon,
            'badge' => url('/pwa/icon-96.png'),
        ];

        $this->sendPayload($subscriptions, $payload, 'private-'.$message->id);
    }

    /**
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @param  array<string, mixed>  $payload
     */
    private function sendPayload(Collection $subscriptions, array $payload, string $topic): void
    {
        $client = $this->makeClient($topic);
        if ($client === null) {
            return;
        }

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (! is_string($encodedPayload)) {
            return;
        }

        foreach ($subscriptions as $row) {
            $client->queueNotification(
                Subscription::create([
                    'endpoint' => $row->endpoint,
                    'publicKey' => $row->public_key,
                    'authToken' => $row->auth_token,
                    'contentEncoding' => $row->content_encoding ?: 'aesgcm',
                ]),
                $encodedPayload
            );
        }

        /** @var MessageSentReport $report */
        foreach ($client->flush() as $report) {
            if ($report->isSuccess()) {
                continue;
            }

            $endpoint = (string) $report->getEndpoint();
            $endpointHash = PushSubscription::hashEndpoint($endpoint);

            if ($report->isSubscriptionExpired()) {
                PushSubscription::query()->where('endpoint_hash', $endpointHash)->delete();

                continue;
            }

            Log::warning('web_push.delivery_failed', [
                'endpoint_hash' => $endpointHash,
                'reason' => $report->getReason(),
            ]);
        }
    }

    private function makeClient(string $topic): ?WebPush
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $client = new WebPush([
            'VAPID' => [
                'subject' => $this->vapidSubject(),
                'publicKey' => $this->vapidPublicKey(),
                'privateKey' => $this->vapidPrivateKey(),
            ],
        ], [
            'TTL' => max(60, (int) config('services.web_push.ttl', 300)),
            'urgency' => (string) config('services.web_push.urgency', 'normal'),
            'batchSize' => max(1, (int) config('services.web_push.batch_size', 200)),
            'topic' => substr($topic, 0, 32),
        ]);

        $client->setAutomaticPadding(false);

        return $client;
    }

    private function vapidSubject(): ?string
    {
        $value = trim((string) config('services.web_push.vapid.subject'));

        return $value !== '' ? $value : null;
    }

    private function vapidPublicKey(): ?string
    {
        $value = trim((string) config('services.web_push.vapid.public_key'));

        return $value !== '' ? $value : null;
    }

    private function vapidPrivateKey(): ?string
    {
        $value = trim((string) config('services.web_push.vapid.private_key'));

        return $value !== '' ? $value : null;
    }

    private function userCanReceiveRoomPush(?User $user, Room $room): bool
    {
        if ($user === null || $user->guest) {
            return false;
        }

        if ((int) $room->access <= Room::ACCESS_PUBLIC) {
            return true;
        }

        if ((int) $room->access === Room::ACCESS_REGISTERED) {
            return true;
        }

        return $user->isVip() || $user->canModerate();
    }

    private function messagePreview(string $message, bool $hasImage): string
    {
        $plain = trim(preg_replace('/\s+/u', ' ', strip_tags($message)) ?? '');
        if ($plain === '' && $hasImage) {
            return 'Нове зображення';
        }
        if ($plain === '') {
            return 'Нове повідомлення';
        }

        return mb_strimwidth($plain, 0, 120, '…', 'UTF-8');
    }

    private function roomUrl(Room $room): string
    {
        $slug = trim((string) ($room->slug ?? ''));
        if ($slug !== '') {
            return url('/chat/'.$slug);
        }

        return url('/chat?room='.(int) $room->room_id);
    }

    private function privateThreadUrl(int $peerUserId, string $peerDisplayName): string
    {
        $query = http_build_query(
            [
                'private_peer' => $peerUserId,
                'private_peer_name' => $peerDisplayName,
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );

        return url('/chat?'.$query);
    }
}
