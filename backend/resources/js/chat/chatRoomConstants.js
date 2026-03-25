/** @typedef {'users'|'friends'|'private'|'rooms'|'ignore'} SidebarTabId */

export const THEME_KEY = 'redpanda-theme';

/** Збереження останньої вкладки сайдбару; відсутній/невалідний ключ → «Люди». */
export const SIDEBAR_TAB_STORAGE_KEY = 'redpanda-chat-sidebar-tab';

export const SIDEBAR_TAB_IDS = ['users', 'friends', 'private', 'rooms', 'ignore'];

/** Пороги idle (сек) — узгоджено з `config/chat.php` (T48). */
export const PRESENCE_AWAY_IDLE_SEC = 180;
export const PRESENCE_INACTIVE_IDLE_SEC = 600;

/** Debounce після Echo `joining()` перед оновленням status/hints по пірах (ms). T126. */
export const PEER_PRESENCE_JOIN_DEBOUNCE_MS = 80;

export const SIDEBAR_TAB_ICONS = {
    users:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>',
    friends:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>',
    private:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>',
    rooms:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
    ignore:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/><path fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" d="M4.75 5.25 L19.25 18.75"/></svg>',
};

/**
 * @returns {SidebarTabId}
 */
export function readStoredSidebarTab() {
    if (typeof localStorage === 'undefined') {
        return 'users';
    }
    try {
        const raw = localStorage.getItem(SIDEBAR_TAB_STORAGE_KEY);
        if (raw && SIDEBAR_TAB_IDS.includes(raw)) {
            return /** @type {SidebarTabId} */ (raw);
        }
    } catch {
        /* */
    }

    return 'users';
}
