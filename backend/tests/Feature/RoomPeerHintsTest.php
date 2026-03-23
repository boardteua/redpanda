<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoomPeerHintsTest extends TestCase
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

    public function test_guest_viewer_receives_empty_peer_hints(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $guest = User::factory()->guest()->create();
        $bob = User::factory()->create([
            'profile_sex' => 'male',
            'profile_sex_hidden' => false,
        ]);

        $this->from(config('app.url'))
            ->actingAs($guest, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/peer-hints?user_ids=".$bob->id)
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_registered_viewer_sees_peer_sex_when_not_hidden(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create();
        $bob = User::factory()->create([
            'profile_sex' => 'female',
            'profile_sex_hidden' => false,
        ]);

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/peer-hints?user_ids=".$bob->id)
            ->assertOk()
            ->assertJsonPath('data.'.(string) $bob->id.'.sex', 'female');
    }

    public function test_hidden_sex_omitted(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $alice = User::factory()->create();
        $bob = User::factory()->create([
            'profile_sex' => 'male',
            'profile_sex_hidden' => true,
        ]);

        $this->from(config('app.url'))
            ->actingAs($alice, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/peer-hints?user_ids=".$bob->id)
            ->assertOk()
            ->assertJsonMissingPath('data.'.(string) $bob->id);
    }

    public function test_index_requires_user_ids(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $user = User::factory()->create();

        $this->from(config('app.url'))
            ->actingAs($user, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/peer-hints")
            ->assertUnprocessable();
    }

    public function test_moderator_sees_chat_upload_disabled_even_when_sex_hidden(): void
    {
        $public = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);
        $mod = User::factory()->moderator()->create();
        $bob = User::factory()->create([
            'profile_sex' => 'male',
            'profile_sex_hidden' => true,
        ]);
        $bob->forceFill(['chat_upload_disabled' => true])->save();

        $this->from(config('app.url'))
            ->actingAs($mod, 'web')
            ->withHeaders($this->statefulHeaders())
            ->getJson("/api/v1/rooms/{$public->room_id}/peer-hints?user_ids=".$bob->id)
            ->assertOk()
            ->assertJsonPath('data.'.(string) $bob->id.'.chat_upload_disabled', true)
            ->assertJsonMissingPath('data.'.(string) $bob->id.'.sex');
    }
}
