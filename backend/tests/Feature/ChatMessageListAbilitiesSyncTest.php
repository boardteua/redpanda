<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * T105: батч-мапа can_edit/can_delete у списку повинна збігатися з Gate на кожному рядку.
 */
class ChatMessageListAbilitiesSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    public function test_room_message_index_abilities_match_gate_per_message(): void
    {
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => 0,
        ]);

        $alice = User::factory()->create(['guest' => false]);
        $bob = User::factory()->create(['guest' => false]);

        $now = time();
        ChatMessage::query()->create([
            'user_id' => $alice->id,
            'post_date' => $now - 120,
            'post_time' => '10:00',
            'post_user' => $alice->user_name,
            'post_message' => 'from alice',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ]);
        ChatMessage::query()->create([
            'user_id' => $bob->id,
            'post_date' => $now - 60,
            'post_time' => '10:01',
            'post_user' => $bob->user_name,
            'post_message' => 'from bob',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ]);

        $response = $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/rooms/'.$room->room_id.'/messages?limit=50');

        $response->assertOk();
        $rows = $response->json('data');
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);

        foreach ($rows as $row) {
            $message = ChatMessage::query()->findOrFail($row['post_id']);
            $this->assertSame(
                Gate::forUser($alice)->allows('update', $message),
                $row['can_edit'],
                'can_edit mismatch for post_id '.$row['post_id'],
            );
            $expectedDelete = $message->post_deleted_at === null
                && Gate::forUser($alice)->allows('delete', $message);
            $this->assertSame(
                $expectedDelete,
                $row['can_delete'],
                'can_delete mismatch for post_id '.$row['post_id'],
            );
        }
    }
}
