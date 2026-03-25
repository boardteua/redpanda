<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\User;
use App\Models\PrivateMessage;
use App\Services\LegacyBoardImport\LegacyBoardTeUaUrlRemapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class LegacyBoardUrlRemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_remaps_board_te_ua_in_chat_and_private(): void
    {
        config(['legacy.url_remap_target_origin' => 'https://rp.example.com']);

        $user = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        DB::table('chat')->insert([
            'user_id' => $user->id,
            'post_date' => 1,
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => 'see https://board.te.ua/uploads/x.png',
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => 'http://www.board.te.ua/avatar/a.gif',
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ]);

        $u2 = User::factory()->create();
        PrivateMessage::query()->create([
            'sender_id' => $user->id,
            'recipient_id' => $u2->id,
            'body' => '<img src="//board.te.ua/uploads/y.jpg">',
            'sent_at' => 2,
            'sent_time' => null,
            'client_message_id' => (string) Str::uuid(),
        ]);

        $svc = app(LegacyBoardTeUaUrlRemapService::class);
        $r = $svc->remapAll(false);

        $this->assertSame(1, $r['chat_message_rows']);
        $this->assertSame(1, $r['chat_avatar_rows']);
        $this->assertSame(2, $r['chat_fields_changed']);
        $this->assertSame(1, $r['private_body_rows']);
        $this->assertSame(1, $r['private_fields_changed']);

        $row = DB::table('chat')->where('user_id', $user->id)->first();
        $this->assertStringContainsString('https://rp.example.com', (string) $row->post_message);
        $this->assertStringNotContainsString('board.te.ua', (string) $row->post_message);
        $this->assertStringStartsWith('https://rp.example.com', (string) $row->avatar);

        $pm = PrivateMessage::query()->first();
        $this->assertStringContainsString('https://rp.example.com', (string) $pm->body);
    }

    public function test_dry_run_does_not_mutate(): void
    {
        config(['legacy.url_remap_target_origin' => 'https://rp.example.com']);

        $user = User::factory()->create();
        $room = Room::query()->create([
            'room_name' => 'R',
            'topic' => null,
            'access' => Room::ACCESS_PUBLIC,
        ]);

        DB::table('chat')->insert([
            'user_id' => $user->id,
            'post_date' => 1,
            'post_time' => '12:00',
            'post_user' => $user->user_name,
            'post_message' => 'https://board.te.ua/x',
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
        ]);

        app(LegacyBoardTeUaUrlRemapService::class)->remapAll(true);

        $row = DB::table('chat')->where('user_id', $user->id)->first();
        $this->assertStringContainsString('board.te.ua', (string) $row->post_message);
    }
}
