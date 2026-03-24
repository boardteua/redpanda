<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Image;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Tests\TestCase;

class UserAvatarApiTest extends TestCase
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

    public function test_registered_user_can_upload_avatar_and_user_resource_has_url(): void
    {
        Storage::fake('chat_images');

        $user = User::factory()->create(['guest' => false]);
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $file = UploadedFile::fake()->image('face.png', 80, 80);

        $response = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/me/avatar', ['image' => $file]);

        $response->assertCreated();
        $response->assertJsonPath('data.guest', false);
        $this->assertNotNull($response->json('data.avatar_url'));

        $user->refresh();
        $this->assertNotNull($user->avatar_image_id);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => 'hello with avatar',
                'client_message_id' => 'f1000000-0000-4000-8000-000000000001',
            ])
            ->assertCreated()
            ->assertJsonPath('data.avatar', $user->resolveAvatarUrl());
    }

    public function test_guest_upload_avatar_returns_403(): void
    {
        Storage::fake('chat_images');

        $guest = User::factory()->guest()->create();
        $file = UploadedFile::fake()->image('g.png', 40, 40);

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/me/avatar', ['image' => $file])
            ->assertForbidden();
    }

    public function test_replacing_avatar_deletes_previous_when_not_used_as_chat_attachment(): void
    {
        Storage::fake('chat_images');

        $user = User::factory()->create(['guest' => false]);

        $first = UploadedFile::fake()->image('a.png', 40, 40);
        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/me/avatar', ['image' => $first])
            ->assertCreated();
        $user->refresh();
        $firstId = (int) $user->avatar_image_id;
        $firstPath = Image::query()->findOrFail($firstId)->disk_path;

        $second = UploadedFile::fake()->image('b.png', 40, 40);
        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/me/avatar', ['image' => $second])
            ->assertCreated();
        $user->refresh();
        $secondId = (int) $user->avatar_image_id;

        $this->assertNotSame($firstId, $secondId);
        $this->assertDatabaseMissing('images', ['id' => $firstId]);
        Storage::disk('chat_images')->assertMissing($firstPath);
    }

    public function test_replacing_avatar_keeps_previous_if_still_chat_attachment(): void
    {
        Storage::fake('chat_images');

        $user = User::factory()->create(['guest' => false]);
        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $imgFile = UploadedFile::fake()->image('attach.png', 40, 40);
        $up = $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/images', ['image' => $imgFile]);
        $up->assertCreated();
        $imageId = (int) $up->json('data.id');

        ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time(),
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => 'x',
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => $imageId,
            'client_message_id' => 'f2000000-0000-4000-8000-000000000002',
        ]);

        $user->forceFill(['avatar_image_id' => $imageId])->save();

        $newAvatar = UploadedFile::fake()->image('new.png', 40, 40);
        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/me/avatar', ['image' => $newAvatar])
            ->assertCreated();
        $user->refresh();

        $this->assertDatabaseHas('images', ['id' => $imageId]);
        $this->assertNotSame($imageId, (int) $user->avatar_image_id);
    }

    public function test_avatar_rejects_dimensions_above_avatar_limit(): void
    {
        Storage::fake('chat_images');

        $user = User::factory()->create(['guest' => false]);
        $file = UploadedFile::fake()->image('huge.png', 4100, 40);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/me/avatar', ['image' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_avatar_rejects_non_image_bytes_even_with_png_client_type(): void
    {
        Storage::fake('chat_images');

        $user = User::factory()->create(['guest' => false]);

        $tmp = tempnam(sys_get_temp_dir(), 'badimg');
        $this->assertNotFalse($tmp);
        // Коректний заголовок PNG, але без валідних чанків — проходить mimetypes, падає на getimagesize (інспектор T19).
        file_put_contents($tmp, "\x89PNG\r\n\x1a\n".str_repeat("\0", 128));
        $file = new SymfonyUploadedFile($tmp, 'disguise.png', 'image/png', UPLOAD_ERR_OK, true);

        try {
            $this->from(config('app.url'))
                ->actingAs($user, 'web')
                ->withHeaders($this->statefulHeaders())
                ->post('/api/v1/me/avatar', ['image' => $file])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['image']);
        } finally {
            if (is_file($tmp)) {
                unlink($tmp);
            }
        }
    }

    public function test_other_user_can_fetch_avatar_image_file_for_profile_avatar(): void
    {
        Storage::fake('chat_images');

        $owner = User::factory()->create(['guest' => false]);
        $viewer = User::factory()->create(['guest' => false]);

        $file = UploadedFile::fake()->image('pub.png', 40, 40);
        $this->from(config('app.url'))
            ->actingAs($owner, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/me/avatar', ['image' => $file])
            ->assertCreated();
        $owner->refresh();
        $imageId = (int) $owner->avatar_image_id;

        $this->from(config('app.url'))
            ->actingAs($viewer, 'web')
            ->withHeaders($this->statefulHeaders())
            ->get('/api/v1/images/'.$imageId.'/file')
            ->assertOk();
    }
}
