<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatLegacyLinkUserAvatarsTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_legacy_dir_missing(): void
    {
        $this->artisan('chat:legacy-link-user-avatars', [
            '--dir' => '/___redpanda_missing_legacy_avatars___/dir',
        ])->assertFailed();
    }

    public function test_links_avatar_from_legacy_directory(): void
    {
        Storage::fake('chat_images');

        $legacyDir = sys_get_temp_dir().'/rp-legacy-av-'.uniqid('', true);
        mkdir($legacyDir, 0700, true);
        $pngPath = $legacyDir.'/ExactNick.png';
        $im = imagecreatetruecolor(2, 2);
        imagepng($im, $pngPath);
        imagedestroy($im);

        Config::set('legacy.avatar_rsync_dest', $legacyDir);

        $user = User::factory()->create([
            'user_name' => 'ExactNick',
            'guest' => false,
            'legacy_imported_at' => now(),
            'avatar_image_id' => null,
        ]);

        $this->artisan('chat:legacy-link-user-avatars')
            ->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->avatar_image_id);
        $this->assertNotNull($user->resolveAvatarUrl());

        Storage::disk('chat_images')->assertExists($user->id.'/avatars/legacy-ExactNick.png');
    }

    public function test_falls_back_to_storage_legacy_avatars_when_env_path_is_host_only(): void
    {
        Storage::fake('chat_images');

        $canonical = storage_path('app/legacy-avatars');
        File::ensureDirectoryExists($canonical);
        $pngPath = $canonical.'/FallbackNick.png';
        $im = imagecreatetruecolor(1, 1);
        imagepng($im, $pngPath);
        imagedestroy($im);

        Config::set('legacy.avatar_rsync_dest', '/var/www/redpanda/backend/storage/app/legacy-avatars');

        $user = User::factory()->create([
            'user_name' => 'FallbackNick',
            'guest' => false,
            'legacy_imported_at' => now(),
            'avatar_image_id' => null,
        ]);

        $this->artisan('chat:legacy-link-user-avatars')
            ->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->avatar_image_id);

        @unlink($pngPath);
    }

    public function test_case_insensitive_match_on_file_stem(): void
    {
        Storage::fake('chat_images');

        $legacyDir = sys_get_temp_dir().'/rp-legacy-av-'.uniqid('', true);
        mkdir($legacyDir, 0700, true);
        $pngPath = $legacyDir.'/MiXeD.gif';
        $im = imagecreatetruecolor(1, 1);
        imagegif($im, $pngPath);
        imagedestroy($im);

        Config::set('legacy.avatar_rsync_dest', $legacyDir);

        $user = User::factory()->create([
            'user_name' => 'mixed',
            'guest' => false,
            'legacy_imported_at' => now(),
            'avatar_image_id' => null,
        ]);

        $this->artisan('chat:legacy-link-user-avatars')
            ->assertSuccessful();

        $user->refresh();
        $this->assertNotNull($user->avatar_image_id);
    }
}
