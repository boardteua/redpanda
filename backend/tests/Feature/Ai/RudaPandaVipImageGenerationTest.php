<?php

namespace Tests\Feature\Ai;

use App\Jobs\GenerateRudaPandaVipImageJob;
use App\Models\ChatMessage;
use App\Models\ChatSetting;
use App\Models\Image;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\RudaPanda\RudaPandaGeneratedImageStore;
use App\Services\Ai\RudaPanda\RudaPandaImageGenerator;
use App\Services\Ai\RudaPanda\RudaPandaModelRouter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RudaPandaVipImageGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_non_vip_user_does_not_generate_or_store(): void
    {
        Storage::fake('chat_images');
        Http::fake();

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create(['guest' => false, 'vip' => false]);

        $job = new GenerateRudaPandaVipImageJob(
            roomId: (int) $room->room_id,
            triggerUserId: (int) $user->id,
            prompt: 'згенеруй картинку кота',
            idempotencyKey: 'k1',
        );

        $job->handle(
            app(RudaPandaModelRouter::class),
            app(RudaPandaImageGenerator::class),
            app(RudaPandaGeneratedImageStore::class),
        );

        $this->assertDatabaseCount('images', 0);
        $this->assertDatabaseCount('chat', 0);
    }

    public function test_moderator_can_generate_image_even_if_not_vip(): void
    {
        config()->set('services.gemini.enabled', true);
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model_image', 'gemini-3.1-flash-image-preview');

        Storage::fake('chat_images');

        // 1x1 PNG
        $pngB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/6Xk6yUAAAAASUVORK5CYII=';

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Ось.'],
                            ['inlineData' => ['mimeType' => 'image/png', 'data' => $pngB64]],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        ChatSetting::query()->firstOrFail()->update(['ai_llm_enabled' => true]);

        $moderator = User::factory()->create([
            'guest' => false,
            'vip' => false,
            'user_rank' => User::RANK_MODERATOR,
        ]);
        $bot = User::factory()->create(['guest' => false, 'vip' => false, 'is_system_bot' => true]);

        $job = new GenerateRudaPandaVipImageJob(
            roomId: (int) $room->room_id,
            triggerUserId: (int) $moderator->id,
            prompt: 'згенеруй картинку кота',
            idempotencyKey: 'k_mod',
        );

        $job->handle(
            app(RudaPandaModelRouter::class),
            app(RudaPandaImageGenerator::class),
            app(RudaPandaGeneratedImageStore::class),
        );

        $this->assertDatabaseCount('images', 1);
        $this->assertDatabaseHas('images', ['user_id' => $bot->id, 'mime' => 'image/png']);

        $this->assertDatabaseCount('chat', 1);
    }

    public function test_vip_generates_stores_image_and_posts_message(): void
    {
        config()->set('services.gemini.enabled', true);
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model_image', 'gemini-3.1-flash-image-preview');

        Storage::fake('chat_images');

        // 1x1 PNG
        $pngB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/6Xk6yUAAAAASUVORK5CYII=';

        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Ось.'],
                            ['inlineData' => ['mimeType' => 'image/png', 'data' => $pngB64]],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        ChatSetting::query()->firstOrFail()->update(['ai_llm_enabled' => true]);
        $vip = User::factory()->create(['guest' => false, 'vip' => true]);
        $bot = User::factory()->create(['guest' => false, 'vip' => false, 'is_system_bot' => true]);

        $job = new GenerateRudaPandaVipImageJob(
            roomId: (int) $room->room_id,
            triggerUserId: (int) $vip->id,
            prompt: 'згенеруй картинку кота',
            idempotencyKey: 'k2',
        );

        $job->handle(
            app(RudaPandaModelRouter::class),
            app(RudaPandaImageGenerator::class),
            app(RudaPandaGeneratedImageStore::class),
        );

        $this->assertDatabaseCount('images', 1);
        $this->assertDatabaseHas('images', ['user_id' => $bot->id, 'mime' => 'image/png']);

        $img = Image::query()->firstOrFail();
        Storage::disk('chat_images')->assertExists($img->disk_path);

        $this->assertDatabaseCount('chat', 1);
        $msg = ChatMessage::query()->firstOrFail();
        $this->assertSame((int) $bot->id, (int) $msg->user_id);
        $this->assertSame((int) $room->room_id, (int) $msg->post_roomid);
        $this->assertSame((int) $img->id, (int) $msg->file);
    }

    public function test_vip_image_job_is_idempotent_for_same_key(): void
    {
        config()->set('services.gemini.enabled', true);
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model_image', 'gemini-3.1-flash-image-preview');

        Storage::fake('chat_images');

        $pngB64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/6Xk6yUAAAAASUVORK5CYII=';
        Http::fake([
            '*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [
                            ['text' => 'Ось.'],
                            ['inlineData' => ['mimeType' => 'image/png', 'data' => $pngB64]],
                        ],
                    ],
                ]],
            ], 200),
        ]);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        ChatSetting::query()->firstOrFail()->update(['ai_llm_enabled' => true]);
        $vip = User::factory()->create(['guest' => false, 'vip' => true]);
        User::factory()->create(['guest' => false, 'vip' => false, 'is_system_bot' => true]);

        $job = new GenerateRudaPandaVipImageJob(
            roomId: (int) $room->room_id,
            triggerUserId: (int) $vip->id,
            prompt: 'згенеруй картинку кота',
            idempotencyKey: 'same-key',
        );

        $job->handle(
            app(RudaPandaModelRouter::class),
            app(RudaPandaImageGenerator::class),
            app(RudaPandaGeneratedImageStore::class),
        );
        $job->handle(
            app(RudaPandaModelRouter::class),
            app(RudaPandaImageGenerator::class),
            app(RudaPandaGeneratedImageStore::class),
        );

        $this->assertDatabaseCount('chat', 1);
    }
}
