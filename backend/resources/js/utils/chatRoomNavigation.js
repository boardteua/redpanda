/** T153 — канонічний шлях `/chat/:roomSlug` і сегмент у `GET /api/v1/rooms/{segment}/…`. */

export const CHAT_DEFAULT_PUBLIC_ROOM_ID = 1;

/**
 * @param {{ slug?: string } | null | undefined} room
 */
export function normalizedRoomSlug(room) {
    if (!room || room.slug == null || String(room.slug).trim() === '') {
        return '';
    }

    return String(room.slug);
}

/**
 * @param {Array<{ room_id: number|string, slug?: string }>} rooms
 * @param {number|string|null|undefined} selectedRoomId
 */
export function apiRoomPathSegment(rooms, selectedRoomId) {
    if (selectedRoomId == null) {
        return '';
    }
    const r = rooms.find((x) => Number(x.room_id) === Number(selectedRoomId));
    if (r && r.slug) {
        return encodeURIComponent(String(r.slug));
    }

    return String(selectedRoomId);
}

/**
 * @param {Array<{ room_id: number|string, slug?: string }>} rooms
 * @param {number|string|null|undefined} selectedRoomId
 * @param {Record<string, string>} [extraQuery]
 */
export function buildChatRoute(rooms, selectedRoomId, extraQuery = {}) {
    const q = { ...extraQuery };
    delete q.room;
    const r = rooms.find((x) => Number(x.room_id) === Number(selectedRoomId));
    if (r && r.slug) {
        return { name: 'chat', params: { roomSlug: r.slug }, query: q };
    }
    if (selectedRoomId != null) {
        return { name: 'chat', query: { ...q, room: String(selectedRoomId) } };
    }

    return { name: 'chat', query: q };
}

/**
 * @param {Array<{ room_id: number|string, slug?: string }>} rooms
 * @param {number|string|null|undefined} selectedRoomId
 */
export function staffContextQuery(rooms, selectedRoomId) {
    const r = rooms.find((x) => Number(x.room_id) === Number(selectedRoomId));
    if (r && r.slug) {
        return { room: String(r.slug) };
    }
    if (selectedRoomId != null) {
        return { room: String(selectedRoomId) };
    }

    return {};
}

/**
 * @param {import('vue-router').Route} route
 */
export function isChatRoute(route) {
    return Boolean(route && route.name === 'chat');
}
