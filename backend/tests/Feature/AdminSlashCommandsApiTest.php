<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminSlashCommandsApiTest extends TestCase
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

    /**
     * @return array{0: Room, 1: Room}
     */
    private function seedTwoPublicRooms(): array
    {
        $a = Room::query()->create(['room_name' => 'T71 A', 'topic' => null, 'access' => 0]);
        $b = Room::query()->create(['room_name' => 'T71 B', 'topic' => null, 'access' => 0]);

        return [$a, $b];
    }

    public function test_slash_setmod_forbidden_for_moderator(): void
    {
        [$r1] = $this->seedTwoPublicRooms();
        $mod = User::factory()->moderator()->create();
        User::factory()->create(['user_name' => 't71bob']);

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$r1->room_id.'/messages', [
                'message' => '/setmod t71bob',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertForbidden();
    }

    public function test_slash_setmod_admin_works(): void
    {
        [$r1] = $this->seedTwoPublicRooms();
        $admin = User::factory()->admin()->create();
        $u = User::factory()->create(['user_name' => 't71setmod', 'user_rank' => User::RANK_USER]);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$r1->room_id.'/messages', [
                'message' => '/setmod t71setmod',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated()
            ->assertJsonPath('meta.slash.name', 'setmod')
            ->assertJsonPath('data.type', 'client_only');

        $this->assertSame(User::RANK_MODERATOR, (int) $u->fresh()->user_rank);
        $this->assertFalse((bool) $u->fresh()->vip);
    }

    public function test_slash_silent_on_updates_setting(): void
    {
        [$r1] = $this->seedTwoPublicRooms();
        $admin = User::factory()->admin()->create();

        $this->assertFalse((bool) ChatSetting::current()->silent_mode);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$r1->room_id.'/messages', [
                'message' => '/silent on',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated()
            ->assertJsonPath('meta.slash.name', 'silent');

        $this->assertTrue((bool) ChatSetting::current()->fresh()->silent_mode);
    }

    public function test_slash_global_creates_row_per_room(): void
    {
        [$a, $b] = $this->seedTwoPublicRooms();
        $admin = User::factory()->admin()->create();
        $cid = (string) Str::uuid();

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$a->room_id.'/messages', [
                'message' => '/global Рядок для всіх',
                'client_message_id' => $cid,
            ])
            ->assertCreated()
            ->assertJsonPath('meta.slash.name', 'global')
            ->assertJsonPath('data.type', 'public');

        $this->assertSame(2, ChatMessage::query()->where('post_message', 'Рядок для всіх')->count());
        $this->assertSame(1, ChatMessage::query()->where('post_roomid', $a->room_id)->where('client_message_id', $cid)->count());
        $bRow = ChatMessage::query()->where('post_roomid', $b->room_id)->where('post_message', 'Рядок для всіх')->first();
        $this->assertNotNull($bRow);
        $this->assertNotSame($cid, $bRow->client_message_id);
        $style = $bRow->post_style;
        $this->assertIsArray($style);
        $this->assertTrue((bool) ($style['global'] ?? false));
    }

    public function test_slash_gsound_forbidden_for_regular_user(): void
    {
        [$r1] = $this->seedTwoPublicRooms();
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$r1->room_id.'/messages', [
                'message' => '/gsound',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertForbidden();
    }

    public function test_slash_invisible_sets_presence_invisible(): void
    {
        [$r1] = $this->seedTwoPublicRooms();
        $admin = User::factory()->admin()->create();
        $this->assertFalse((bool) $admin->presence_invisible);

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$r1->room_id.'/messages', [
                'message' => '/invisible',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated()
            ->assertJsonPath('meta.slash.name', 'invisible');

        $this->assertTrue((bool) $admin->fresh()->presence_invisible);
    }
}
