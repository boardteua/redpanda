<?php

namespace Tests\Feature\Ai;

use App\Models\ChatSetting;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RudaPandaLlmDebugMetaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_post_room_message_includes_ruda_panda_llm_debug_when_config_enabled(): void
    {
        config()->set('chat.ruda_panda_llm_debug_console', true);
        config()->set('app.debug', true);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
            'ai_bot_enabled' => true,
        ]);
        ChatSetting::query()->firstOrFail()->update(['ai_llm_enabled' => true]);

        $user = User::factory()->create(['guest' => false]);

        $response = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders(['Referer' => config('app.url')])
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => 'панда, привіт',
                'client_message_id' => (string) Str::uuid(),
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['meta' => ['ruda_panda_llm_debug']]);
        $debug = $response->json('meta.ruda_panda_llm_debug');
        $this->assertIsArray($debug);
        $this->assertArrayHasKey('version', $debug);
        $this->assertSame('mention', $debug['trigger'] ?? null);
    }

    public function test_post_room_message_omits_ruda_panda_llm_debug_when_config_disabled(): void
    {
        config()->set('chat.ruda_panda_llm_debug_console', false);
        config()->set('app.debug', true);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
            'ai_bot_enabled' => true,
        ]);
        ChatSetting::query()->firstOrFail()->update(['ai_llm_enabled' => true]);

        $user = User::factory()->create(['guest' => false]);

        $response = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders(['Referer' => config('app.url')])
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => 'панда, привіт',
                'client_message_id' => (string) Str::uuid(),
            ]);

        $response->assertCreated();
        $this->assertArrayNotHasKey('ruda_panda_llm_debug', $response->json('meta') ?? []);
    }
}
