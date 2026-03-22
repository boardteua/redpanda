<?php

namespace Tests\Feature;

use App\Models\BannedIp;
use App\Models\ChatMessage;
use App\Models\FilterWord;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ModerationApiTest extends TestCase
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

    public function test_banned_ip_blocks_guest_auth(): void
    {
        $ip = '203.0.113.77';
        BannedIp::query()->create(['ip' => $ip]);

        $this->from(config('app.url'))
            ->withServerVariables(['REMOTE_ADDR' => $ip])
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/auth/guest', [])
            ->assertForbidden()
            ->assertJsonPath('message', 'Доступ з цієї IP-адреси заблоковано.');
    }

    public function test_filter_word_replaces_in_public_message(): void
    {
        FilterWord::query()->create(['word' => 'badword']);

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
                'message' => 'Hello BADWORD here',
                'client_message_id' => $clientId,
            ])
            ->assertCreated();

        $msg = ChatMessage::query()->where('client_message_id', $clientId)->firstOrFail();
        $this->assertStringContainsString('*******', $msg->post_message);
        $this->assertStringNotContainsString('BADWORD', $msg->post_message);
    }

    public function test_muted_user_cannot_post_to_room(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $victim = User::factory()->create();
        $victim->forceFill(['mute_until' => time() + 3600])->save();

        $this->from(config('app.url'))
            ->actingAs($victim, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'Hi',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Ви в муті й не можете надсилати повідомлення.');
    }

    public function test_kicked_user_cannot_post_to_room(): void
    {
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $victim = User::factory()->create();
        $victim->forceFill(['kick_until' => time() + 3600])->save();

        $this->from(config('app.url'))
            ->actingAs($victim, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/rooms/{$room->room_id}/messages", [
                'message' => 'Hi',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertForbidden()
            ->assertJsonPath('message', 'Вас тимчасово відключено від чату.');
    }

    public function test_non_moderator_cannot_access_mod_routes(): void
    {
        $user = User::factory()->create(['user_rank' => User::RANK_USER]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/banned-ips')
            ->assertForbidden();
    }

    public function test_filter_word_min_length_two(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/mod/filter-words', ['word' => 'x'])
            ->assertUnprocessable();
    }

    public function test_moderator_cannot_manage_banned_ips(): void
    {
        $mod = User::factory()->moderator()->create();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/mod/banned-ips', ['ip' => '198.51.100.99'])
            ->assertForbidden();
    }

    public function test_admin_can_ban_ip(): void
    {
        $admin = User::factory()->admin()->create();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/mod/banned-ips', ['ip' => '198.51.100.10'])
            ->assertCreated();

        $this->assertDatabaseHas('banned_ips', ['ip' => '198.51.100.10']);
    }

    public function test_moderator_can_clear_mute(): void
    {
        $mod = User::factory()->moderator()->create();
        $victim = User::factory()->create();
        $victim->forceFill(['mute_until' => time() + 9999])->save();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson("/api/v1/mod/users/{$victim->id}/mute", ['minutes' => 0])
            ->assertOk()
            ->assertJsonPath('data.mute_until', null);
    }
}
