<?php

namespace Tests\Feature;

use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Tests\TestCase;

class PublicUserAvatarFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_public_avatar_returns_image_without_auth(): void
    {
        Storage::fake('chat_images');

        $user = User::factory()->create();
        $path = $user->id.'/avatars/'.Str::random(8).'.jpg';
        Storage::disk('chat_images')->put($path, 'fake-binary');

        $image = Image::query()->create([
            'user_id' => $user->id,
            'user_name' => $user->user_name,
            'disk_path' => $path,
            'file_name' => 'face.jpg',
            'mime' => 'image/jpeg',
            'size_bytes' => 12,
            'date_sent' => time(),
        ]);
        $user->forceFill(['avatar_image_id' => $image->id])->save();

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.public-user-avatar',
            now()->addHour(),
            ['user' => $user->id],
        );

        $this->get($signedUrl)
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $fromUser = $user->fresh()->signedPublicAvatarUrlForPush();
        $this->assertNotNull($fromUser);
        $this->assertStringContainsString('signature=', $fromUser);
        $this->get($fromUser)->assertOk();
    }

    public function test_unsigned_request_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->get('/api/v1/public/users/'.$user->id.'/avatar')
            ->assertForbidden();
    }

    public function test_user_without_avatar_returns_404(): void
    {
        $user = User::factory()->create(['avatar_image_id' => null]);

        $signedUrl = URL::temporarySignedRoute(
            'api.v1.public-user-avatar',
            now()->addHour(),
            ['user' => $user->id],
        );

        $this->get($signedUrl)->assertNotFound();
    }
}
