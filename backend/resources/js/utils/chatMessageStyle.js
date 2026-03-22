/** @typedef {{ bold: boolean, italic: boolean, underline: boolean, bg: string|null, fg: string|null }} ComposerStyle */

export const CHAT_COMPOSER_STYLE_STORAGE_KEY = 'rp_chat_composer_style_v1';

export const CHAT_STYLE_BG_KEYS = ['amber', 'mint', 'sky', 'lavender', 'rose', 'sand'];

export const CHAT_STYLE_FG_KEYS = ['blue', 'emerald', 'rose', 'violet', 'amber', 'slate'];

/** @returns {ComposerStyle} */
export function defaultComposerStyle() {
    return {
        bold: false,
        italic: false,
        underline: false,
        bg: null,
        fg: null,
    };
}

/** @returns {ComposerStyle} */
export function readComposerStyleFromStorage() {
    try {
        const raw = localStorage.getItem(CHAT_COMPOSER_STYLE_STORAGE_KEY);
        if (!raw) {
            return defaultComposerStyle();
        }
        const o = JSON.parse(raw);
        if (!o || typeof o !== 'object') {
            return defaultComposerStyle();
        }
        const next = defaultComposerStyle();
        next.bold = Boolean(o.bold);
        next.italic = Boolean(o.italic);
        next.underline = Boolean(o.underline);
        if (o.bg && CHAT_STYLE_BG_KEYS.includes(o.bg)) {
            next.bg = o.bg;
        }
        if (o.fg && CHAT_STYLE_FG_KEYS.includes(o.fg)) {
            next.fg = o.fg;
        }
        if (next.bg && next.fg) {
            next.fg = null;
        }

        return next;
    } catch {
        return defaultComposerStyle();
    }
}

/** @param {ComposerStyle} style */
export function persistComposerStyle(style) {
    try {
        localStorage.setItem(CHAT_COMPOSER_STYLE_STORAGE_KEY, JSON.stringify(style));
    } catch {
        /* ignore quota / private mode */
    }
}

/**
 * Нормалізація з API / WS (лише дозволені ключі).
 * @param {unknown} raw
 * @returns {ComposerStyle|null}
 */
export function normalizePostStyleFromApi(raw) {
    if (!raw || typeof raw !== 'object') {
        return null;
    }
    const o = /** @type {Record<string, unknown>} */ (raw);
    const bold = Boolean(o.bold);
    const italic = Boolean(o.italic);
    const underline = Boolean(o.underline);
    const bg = o.bg && CHAT_STYLE_BG_KEYS.includes(String(o.bg)) ? String(o.bg) : null;
    const fgRaw = o.fg && CHAT_STYLE_FG_KEYS.includes(String(o.fg)) ? String(o.fg) : null;
    const fg = bg ? null : fgRaw;
    if (!bold && !italic && !underline && !bg && !fg) {
        return null;
    }

    return { bold, italic, underline, bg, fg };
}

/**
 * @param {ComposerStyle|null} style
 * @returns {string[]}
 */
export function chatMessageBodyClassList(style) {
    if (!style) {
        return [];
    }
    const list = [];
    if (style.bold) {
        list.push('rp-chat-msg-bold');
    }
    if (style.italic) {
        list.push('rp-chat-msg-italic');
    }
    if (style.underline) {
        list.push('rp-chat-msg-underline');
    }
    if (style.bg) {
        list.push(`rp-chat-msg-bg-${style.bg}`);
    } else if (style.fg) {
        list.push(`rp-chat-msg-fg-${style.fg}`);
    }

    return list;
}
