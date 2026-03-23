/**
 * T93 — єдиний стиль заголовка вкладки: бренд + em dash + контекст.
 * Текст лише plain (без HTML у document.title).
 */
export const CHAT_BRAND_TITLE = 'Чат Рудої Панди';
export const CHAT_DOC_TITLE_SEP = ' — ';

export function sanitizeTitleSegment(raw, maxLen = 120) {
    let s = String(raw ?? '')
        .replace(/[\u0000-\u001F\u007F]/g, '')
        .trim();
    if (s.length > maxLen) {
        s = `${s.slice(0, maxLen - 1)}…`;
    }

    return s;
}

/**
 * Заголовок для маршруту `/chat` залежно від стану завантаження та поточної кімнати.
 */
export function buildChatRoomBrowserTitle({
    loadError,
    loadingRooms,
    roomsLength,
    selectedRoomId,
    roomName,
}) {
    const base = CHAT_BRAND_TITLE;
    const sep = CHAT_DOC_TITLE_SEP;

    if (loadError) {
        return `${base}${sep}помилка завантаження`;
    }
    if (loadingRooms) {
        return `${base}${sep}завантаження…`;
    }
    if (roomsLength === 0) {
        return `${base}${sep}немає кімнат`;
    }

    const name = sanitizeTitleSegment(roomName);
    if (name) {
        return `${base}${sep}${name}`;
    }
    if (selectedRoomId != null) {
        return `${base}${sep}кімната`;
    }

    return base;
}
