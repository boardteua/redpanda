/**
 * T65: сповіщення newpost / pmsound з урахуванням autoplay (перша взаємодія).
 */

const URL_NEWPOST = '/sounds/newpost.mp3';
const URL_PMSOUND = '/sounds/pmsound.mp3';
/** T71 /gsound — існуючий актив у репо (whistle). */
const URL_GSOUND = '/sounds/whistle.mp3';

let userActivated = false;

export function markChatSoundUserActivated() {
    userActivated = true;
}

export function isChatSoundUserActivated() {
    return userActivated;
}

function defaultPrefs() {
    return {
        public_messages: true,
        mentions: true,
        private: true,
        volume_percent: 80,
    };
}

function effectivePrefs(user) {
    if (!user || user.guest) {
        return defaultPrefs();
    }

    return { ...defaultPrefs(), ...(user.notification_sound_prefs || {}) };
}

/**
 * @param {string} url
 * @param {{ notification_sound_prefs?: object, guest?: boolean } | null} user
 */
export function playChatNotificationSound(url, user) {
    if (!userActivated || typeof window === 'undefined') {
        return;
    }
    const p = effectivePrefs(user);
    const pct = Math.max(0, Math.min(100, Number(p.volume_percent) || 80));
    const audio = new Audio(url);
    audio.volume = pct / 100;
    audio.play().catch(() => {});
}

/**
 * Кімнатний звук: не від себе; у фоновій вкладці — лише якщо legacySoundEveryPost (T75).
 * Інлайн-приват у стрічці — без newpost (отримувач вже чує pmsound на private.*).
 *
 * @param {{ notification_sound_prefs?: object, guest?: boolean, id?: number } | null} user
 * @param {{ userId?: number, legacySoundEveryPost?: boolean, type?: string }} opts
 */
export function maybePlayNewPostSound(user, opts = {}) {
    if (!user || typeof document === 'undefined') {
        return;
    }
    if (opts.chatSilentMode) {
        return;
    }
    const { userId, legacySoundEveryPost = false, type } = opts;
    if (userId != null && Number(userId) === Number(user.id)) {
        return;
    }
    if (type === 'inline_private' || type === 'client_only') {
        return;
    }
    const p = effectivePrefs(user);
    if (!p.public_messages) {
        return;
    }
    if (!legacySoundEveryPost && document.visibilityState !== 'visible') {
        return;
    }
    playChatNotificationSound(URL_NEWPOST, user);
}

/**
 * @param {{ notification_sound_prefs?: object, guest?: boolean } | null} user
 */
export function maybePlayPrivateMessageSound(user, chatSilentMode = false) {
    if (!user || typeof document === 'undefined') {
        return;
    }
    if (chatSilentMode) {
        return;
    }
    const p = effectivePrefs(user);
    if (!p.private) {
        return;
    }
    playChatNotificationSound(URL_PMSOUND, user);
}

/**
 * Глобальний сигнал /gsound (T71): не від себе; ігнор при silent_mode.
 *
 * @param {{ notification_sound_prefs?: object, guest?: boolean, id?: number } | null} user
 * @param {{ actorUserId?: number, chatSilentMode?: boolean }} opts
 */
export function maybePlayGlobalGsound(user, opts = {}) {
    if (!user || typeof document === 'undefined') {
        return;
    }
    if (opts.chatSilentMode) {
        return;
    }
    const aid = opts.actorUserId;
    if (aid != null && Number(aid) === Number(user.id)) {
        return;
    }
    playChatNotificationSound(URL_GSOUND, user);
}
