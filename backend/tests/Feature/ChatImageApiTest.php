<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChatImageApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        Storage::fake('chat_images');
    }

    /**
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => config('app.url')];
    }

    private function seedPublicRoom(): Room
    {
        return Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
    }

    public function test_guest_can_upload_and_post_message_with_image(): void
    {
        $room = $this->seedPublicRoom();
        $guest = User::factory()->guest()->create();
        $file = UploadedFile::fake()->image('shot.jpg', 80, 80);

        $up = $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/images', ['image' => $file]);

        $up->assertCreated();
        $imageId = $up->json('data.id');
        $this->assertNotNull($imageId);

        $clientId = 'a1000000-0000-4000-8000-000000000001';

        $post = $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => '',
                'image_id' => $imageId,
                'client_message_id' => $clientId,
            ]);

        $post->assertCreated()
            ->assertJsonPath('data.file', $imageId)
            ->assertJsonPath('data.image.id', $imageId);

        $this->assertSame(1, ChatMessage::query()->where('file', $imageId)->count());
    }

    public function test_message_without_text_or_image_is_422(): void
    {
        $room = $this->seedPublicRoom();
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => '   ',
                'client_message_id' => 'b2000000-0000-4000-8000-000000000002',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['message']);
    }

    public function test_cannot_attach_another_users_image(): void
    {
        $room = $this->seedPublicRoom();
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $file = UploadedFile::fake()->image('a.png', 40, 40);
        $up = $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/images', ['image' => $file]);
        $up->assertCreated();
        $imageId = $up->json('data.id');
        $this->assertNotNull($imageId);
        $this->assertNotSame((int) $alice->id, (int) $bob->id);
        $this->assertDatabaseHas('images', [
            'id' => $imageId,
            'user_id' => $alice->id,
        ]);

        Sanctum::actingAs($bob);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => 'hi',
                'image_id' => $imageId,
                'client_message_id' => 'c3000000-0000-4000-8000-000000000003',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_id']);
    }

    public function test_peer_can_download_image_from_shared_room(): void
    {
        $room = $this->seedPublicRoom();
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $file = UploadedFile::fake()->image('x.webp', 20, 20);
        $imageId = $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/images', ['image' => $file])
            ->json('data.id');

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->postJson('/api/v1/rooms/'.$room->room_id.'/messages', [
                'message' => 'pic',
                'image_id' => $imageId,
                'client_message_id' => 'd4000000-0000-4000-8000-000000000004',
            ])
            ->assertCreated();

        $this->actingAs($bob, 'web')
            ->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->get('/api/v1/images/'.$imageId.'/file')
            ->assertOk();
    }

    public function test_orphan_image_not_readable_by_other_user(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $file = UploadedFile::fake()->image('o.gif', 10, 10);
        $imageId = $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/images', ['image' => $file])
            ->json('data.id');

        Sanctum::actingAs($bob);

        $this->from(config('app.url'))
            ->withHeaders($this->statefulHeaders())
            ->get('/api/v1/images/'.$imageId.'/file')
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_upload(): void
    {
        $file = UploadedFile::fake()->image('n.jpg', 10, 10);

        $this->from(config('app.url'))
            ->withHeaders(array_merge($this->statefulHeaders(), ['Accept' => 'application/json']))
            ->post('/api/v1/images', ['image' => $file])
            ->assertUnauthorized();
    }
}
