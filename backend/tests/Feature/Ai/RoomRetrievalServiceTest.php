<?php

namespace Tests\Feature\Ai;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\User;
use App\Services\Ai\RudaPanda\RoomRetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RoomRetrievalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_retrieval_returns_ranked_snippets_and_excludes_system_bots(): void
    {
        config()->set('chat.ai_retrieval_window_days', 30);
        config()->set('chat.ai_retrieval_scan_limit', 200);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $bot = User::factory()->create(['is_system_bot' => true, 'user_name' => 'Ruda Panda']);
        $alice = User::factory()->create(['is_system_bot' => false, 'user_name' => 'Alice']);
        $bob = User::factory()->create(['is_system_bot' => false, 'user_name' => 'Bob']);

        // Bot message contains lots of overlap but must be excluded from corpus.
        $botMsg = ChatMessage::query()->create([
            'user_id' => $bot->id,
            'post_date' => time() - 600,
            'post_time' => date('H:i'),
            'post_user' => $bot->user_name,
            'post_message' => 'Shimano Deore Deore Deore налаштувати перемикач.',
            'post_style' => null,
            'post_color' => 'system',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        $low = ChatMessage::query()->create([
            'user_id' => $alice->id,
            'post_date' => time() - 500,
            'post_time' => date('H:i'),
            'post_user' => $alice->user_name,
            'post_message' => 'Я купив Shimano Deore.',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        $high = ChatMessage::query()->create([
            'user_id' => $bob->id,
            'post_date' => time() - 400,
            'post_time' => date('H:i'),
            'post_user' => $bob->user_name,
            'post_message' => 'Підкажіть, як налаштувати Shimano Deore та перемикач на велосипеді?',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        $svc = $this->app->make(RoomRetrievalService::class);
        $snippets = $svc->retrieveRelevantSnippets($room, 'як налаштувати Shimano Deore перемикач?', excludePostId: null, maxSnippets: 5);

        $this->assertNotEmpty($snippets);
        $ids = array_map(static fn (array $r) => (int) $r['post_id'], $snippets);

        $this->assertContains((int) $low->post_id, $ids);
        $this->assertContains((int) $high->post_id, $ids);
        $this->assertNotContains((int) $botMsg->post_id, $ids);

        // Best match should come first.
        $this->assertSame((int) $high->post_id, (int) $snippets[0]['post_id']);
    }

    public function test_retrieval_respects_cutoff_window_and_exclude_post_id(): void
    {
        config()->set('chat.ai_retrieval_window_days', 1);
        config()->set('chat.ai_retrieval_scan_limit', 200);

        $room = Room::query()->create([
            'room_name' => 'Public',
            'topic' => null,
            'access' => 0,
        ]);

        $user = User::factory()->create(['is_system_bot' => false, 'user_name' => 'Alice']);

        $old = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time() - (3 * 24 * 3600),
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Shimano Deore налаштування (старий пост).',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        $recent = ChatMessage::query()->create([
            'user_id' => $user->id,
            'post_date' => time() - 300,
            'post_time' => date('H:i'),
            'post_user' => $user->user_name,
            'post_message' => 'Shimano Deore налаштування (свіжий пост).',
            'post_style' => null,
            'post_color' => 'user',
            'post_roomid' => $room->room_id,
            'type' => 'public',
            'post_target' => null,
            'avatar' => null,
            'file' => 0,
            'client_message_id' => (string) Str::uuid(),
            'moderation_flag_at' => null,
        ]);

        $svc = $this->app->make(RoomRetrievalService::class);
        $snippets = $svc->retrieveRelevantSnippets($room, 'Shimano Deore налаштувати', excludePostId: (int) $recent->post_id, maxSnippets: 5);

        $ids = array_map(static fn (array $r) => (int) $r['post_id'], $snippets);
        $this->assertNotContains((int) $old->post_id, $ids);
        $this->assertNotContains((int) $recent->post_id, $ids);
    }
}

