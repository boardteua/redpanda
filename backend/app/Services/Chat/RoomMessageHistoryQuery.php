<?php

namespace App\Services\Chat;

use App\Models\ChatMessage;
use App\Models\Room;
use App\Models\RoomReadState;
use Illuminate\Http\Request;

final class RoomMessageHistoryQuery
{
    public function __construct(
        private readonly RedPandaBotRoomOpenTriggers $redPandaBotRoomOpenTriggers,
    ) {}

    public function execute(Request $request, Room $room): RoomMessageHistoryResult
    {
        $validated = $request->validate([
            'before' => ['sometimes', 'integer', 'min:1'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'since_read' => ['sometimes', 'boolean'],
        ]);

        $limit = (int) ($validated['limit'] ?? 50);
        $before = isset($validated['before']) ? (int) $validated['before'] : null;
        $uid = (int) $request->user()->id;

        if ($before === null) {
            $this->redPandaBotRoomOpenTriggers->handle($request->user(), $room);
        }

        $lastReadPostId = RoomReadState::query()
            ->where('user_id', $uid)
            ->where('room_id', $room->room_id)
            ->value('last_read_post_id');

        $query = ChatMessage::query()
            ->visibleInRoomForUser($room, $uid);
        IgnoredRoomMessageVisibility::scopeExcludeIgnoredAuthors($query, $request->user());
        $query->orderByDesc('post_id');

        if ($before !== null) {
            $query->where('post_id', '<', $before);
        }

        $page = $query->limit($limit)->get();
        $page->loadMissing('room');
        $page = $page->sortBy('post_id')->values();

        $nextCursor = $page->isNotEmpty() ? (int) $page->first()->post_id : null;
        $hasMoreOlder = false;
        if ($nextCursor !== null) {
            if ($page->count() < $limit) {
                $hasMoreOlder = false;
            } else {
                $hasMoreOlder = (clone $query)
                    ->where('post_id', '<', $nextCursor)
                    ->exists();
            }
        }

        $slugSource = $room->slugBindingSource ?? null;
        $slugRedirect = is_string($slugSource)
            && $slugSource !== ''
            && strtolower($slugSource) !== strtolower((string) $room->slug);

        $meta = [
            'next_cursor' => $nextCursor,
            'has_more_older' => $hasMoreOlder,
            'last_read_post_id' => $lastReadPostId !== null ? (int) $lastReadPostId : null,
            'slug_redirect' => $slugRedirect,
            'canonical_slug' => (string) $room->slug,
            'canonical_room_id' => (int) $room->room_id,
        ];

        if ($request->boolean('since_read')) {
            $floor = (int) ($lastReadPostId ?? 0);
            $firstUnread = $page->first(static fn (ChatMessage $m) => (int) $m->post_id > $floor);
            $meta['first_unread_post_id'] = $firstUnread !== null ? (int) $firstUnread->post_id : null;
        }

        return new RoomMessageHistoryResult($page, $meta);
    }
}
