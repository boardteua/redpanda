<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\FilterWord;
use App\Models\PrivateMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StopWordAutomoderationTest extends TestCase
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

    public function test_reject_action_blocks_public_message(): void
    {
        FilterWord::query()->create([
            'word' => 'spamterm',
            'category' => 't',
            'match_mode' => FilterWord::MATCH_SUBSTRING,
            'action' => FilterWord::ACTION_REJECT,
            'mute_minutes' => null,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();
        $clientId = (string) Str::uuid();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'hello spamTERM x',
                'client_message_id' => $clientId,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Повідомлення не пройшло автоматичну модерацію.');

        $this->assertNull(ChatMessage::query()->where('client_message_id', $clientId)->first());
    }

    public function test_whole_word_reject_does_not_match_inside_longer_token(): void
    {
        FilterWord::query()->create([
            'word' => 'bad',
            'match_mode' => FilterWord::MATCH_WHOLE_WORD,
            'action' => FilterWord::ACTION_REJECT,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();
        $clientId = (string) Str::uuid();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'badminton is ok',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();
    }

    public function test_flag_action_sets_moderation_flag_at(): void
    {
        FilterWord::query()->create([
            'word' => 'flagme',
            'match_mode' => FilterWord::MATCH_SUBSTRING,
            'action' => FilterWord::ACTION_FLAG,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();
        $clientId = (string) Str::uuid();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'hello flagme',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();

        $msg = ChatMessage::query()->where('client_message_id', $clientId)->firstOrFail();
        $this->assertNotNull($msg->moderation_flag_at);
        $this->assertGreaterThan(0, (int) $msg->moderation_flag_at);
    }

    public function test_temp_mute_rejects_and_sets_mute_until(): void
    {
        FilterWord::query()->create([
            'word' => 'muteme',
            'match_mode' => FilterWord::MATCH_SUBSTRING,
            'action' => FilterWord::ACTION_TEMP_MUTE,
            'mute_minutes' => 5,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();
        $clientId = (string) Str::uuid();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'muteme please',
                'client_message_id' => $clientId,
            ])
            ->assertUnprocessable();

        $this->assertNull(ChatMessage::query()->where('client_message_id', $clientId)->first());
        $user->refresh();
        $this->assertNotNull($user->mute_until);
        $this->assertGreaterThan(time(), (int) $user->mute_until);
    }

    public function test_moderator_bypasses_reject_rules(): void
    {
        FilterWord::query()->create([
            'word' => 'nope',
            'action' => FilterWord::ACTION_REJECT,
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $mod = User::factory()->moderator()->create();
        $clientId = (string) Str::uuid();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'nope raw',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();

        $msg = ChatMessage::query()->where('client_message_id', $clientId)->firstOrFail();
        $this->assertStringContainsString('nope', $msg->post_message);
        $this->assertNull($msg->moderation_flag_at);
    }

    public function test_private_message_not_blocked_by_room_reject_rule(): void
    {
        FilterWord::query()->create([
            'word' => 'secretbad',
            'action' => FilterWord::ACTION_REJECT,
        ]);

        $peer = User::factory()->create();
        $sender = User::factory()->create();
        $clientId = (string) Str::uuid();

        $this->from(config('app.url'))
            ->actingAs($sender, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/private/peers/{$peer->id}/messages", [
                'message' => 'hello secretBAD world',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();

        $row = PrivateMessage::query()
            ->where('sender_id', $sender->id)
            ->where('client_message_id', $clientId)
            ->firstOrFail();
        $this->assertStringContainsString('secretbad', mb_strtolower($row->body));
    }
}
