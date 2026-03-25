<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChatSanitizeImportedHtmlTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_change_post_message(): void
    {
        [$postId, $junk] = $this->insertChatRowWithJunkSpan();

        $this->artisan('chat:sanitize-imported-html', ['--dry-run' => true])
            ->assertSuccessful();

        $row = DB::table('chat')->where('post_id', $postId)->first();
        $this->assertSame($junk, (string) $row->post_message);
    }

    public function test_sanitize_strips_junk_span(): void
    {
        [$postId, $junk] = $this->insertChatRowWithJunkSpan();

        $this->artisan('chat:sanitize-imported-html')
            ->assertSuccessful();

        $row = DB::table('chat')->where('post_id', $postId)->first();
        $this->assertSame('keep', (string) $row->post_message);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function insertChatRowWithJunkSpan(): array
    {
        $user = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        $junk = '<span style=" color:; background:;"></span>keep';

        DB::table('chat')->insert([
            'user_id' => $user->id,
            'post_date' => 1,
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => $junk,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ]);

        return [(int) DB::table('chat')->max('post_id'), $junk];
    }
}
