<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\User;
use App\Models\UserWebPushPrivatePeerMute;
use App\Models\UserWebPushRoomMute;
use App\Services\Push\WebPushPreferenceEvaluator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebPushPreferenceEvaluatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_push_blocked_when_master_disabled(): void
    {
        $eval = new WebPushPreferenceEvaluator;
        $user = User::factory()->create(['web_push_master_enabled' => false]);
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $this->assertFalse($eval->shouldDeliverRoomWebPush($user, $room));
    }

    public function test_room_push_blocked_when_room_muted(): void
    {
        $eval = new WebPushPreferenceEvaluator;
        $user = User::factory()->create(['web_push_master_enabled' => true]);
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);
        UserWebPushRoomMute::query()->create([
            'user_id' => $user->id,
            'room_id' => $room->room_id,
        ]);

        $this->assertFalse($eval->shouldDeliverRoomWebPush($user, $room));
    }

    public function test_private_push_blocked_when_peer_muted(): void
    {
        $eval = new WebPushPreferenceEvaluator;
        $recipient = User::factory()->create(['web_push_master_enabled' => true]);
        $sender = User::factory()->create();
        UserWebPushPrivatePeerMute::query()->create([
            'user_id' => $recipient->id,
            'peer_user_id' => $sender->id,
        ]);

        $this->assertFalse($eval->shouldDeliverPrivateWebPush($recipient, (int) $sender->id));
    }
}
