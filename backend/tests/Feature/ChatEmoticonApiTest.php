<?php

namespace Tests\Feature;

use App\Models\ChatEmoticon;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ChatEmoticonApiTest extends TestCase
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

    public function test_public_list_returns_only_active_sorted(): void
    {
        $user = User::factory()->create();
        ChatEmoticon::factory()->create([
            'code' => 'z_last',
            'display_name' => 'Z',
            'file_name' => 'z.gif',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        ChatEmoticon::factory()->create([
            'code' => 'a_first',
            'display_name' => 'A',
            'file_name' => 'a.gif',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        ChatEmoticon::factory()->create([
            'code' => 'hidden',
            'file_name' => 'h.gif',
            'is_active' => false,
        ]);

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/chat/emoticons')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'a_first')
            ->assertJsonPath('data.1.code', 'z_last');
    }

    public function test_non_admin_cannot_access_mod_emoticons(): void
    {
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/mod/emoticons')
            ->assertForbidden();
    }

    public function test_admin_can_create_with_upload_and_reject_duplicate_code(): void
    {
        $admin = User::factory()->admin()->create();
        $dir = public_path('emoticon');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $gif = UploadedFile::fake()->create('wave.gif', 20, 'image/gif');

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/mod/emoticons', [
                'code' => 'wave',
                'display_name' => 'Хвиля',
                'sort_order' => 5,
                'file' => $gif,
            ])
            ->assertCreated()
            ->assertJsonPath('data.code', 'wave')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('chat_emoticons', [
            'code' => 'wave',
            'display_name' => 'Хвиля',
            'sort_order' => 5,
        ]);

        $row = ChatEmoticon::query()->where('code', 'wave')->first();
        $this->assertNotNull($row);
        $path = public_path('emoticon/'.$row->file_name);
        $this->assertFileExists($path);

        $gif2 = UploadedFile::fake()->create('wave2.gif', 20, 'image/gif');
        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->post('/api/v1/mod/emoticons', [
                'code' => 'wave',
                'display_name' => 'Дубль',
                'file' => $gif2,
            ])
            ->assertStatus(422);
    }

    public function test_admin_can_patch_and_delete_emoticon(): void
    {
        $admin = User::factory()->admin()->create();
        $row = ChatEmoticon::factory()->create([
            'code' => 'bye',
            'display_name' => 'Бувай',
            'file_name' => 'bye.gif',
        ]);
        $dir = public_path('emoticon');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
        File::put($dir.DIRECTORY_SEPARATOR.'bye.gif', 'GIF87a');

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->patchJson('/api/v1/mod/emoticons/'.$row->id, [
                'is_active' => false,
                'display_name' => 'Па-па',
            ])
            ->assertOk()
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.display_name', 'Па-па');

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson('/api/v1/chat/emoticons')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->from(config('app.url'))
            ->actingAs($admin, 'web')
            ->withHeaders($this->statefulHeaders())
            ->delete('/api/v1/mod/emoticons/'.$row->id)
            ->assertNoContent();

        $this->assertDatabaseMissing('chat_emoticons', ['id' => $row->id]);
        $this->assertFileDoesNotExist(public_path('emoticon/bye.gif'));
    }
}
