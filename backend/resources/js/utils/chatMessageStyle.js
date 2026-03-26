/** @typedef {{ bold: boolean, italic: boolean, underline: boolean, bg: string|null, fg: string|null }} ComposerStyle */

export const CHAT_COMPOSER_STYLE_STORAGE_KEY = 'rp_chat_composer_style_v1';

export const CHAT_STYLE_BG_KEYS = ['amber', 'mint', 'sky', 'lavender', 'rose', 'sand'];

export const CHAT_STYLE_FG_KEYS = ['blue', 'emerald', 'rose', 'violet', 'amber', 'slate'];

/** Мітки для UI палітри композера (ключі збігаються з CHAT_STYLE_*). */
export const COMPOSER_BG_PALETTE = [
    { key: 'amber', label: 'Жовтогарячий фон' },
    { key: 'mint', label: 'Мʼятний фон' },
    { key: 'sky', label: 'Блакитний фон' },
    { key: 'lavender', label: 'Лавандовий фон' },
    { key: 'rose', label: 'Рожевий фон' },
    { key: 'sand', label: 'Пісочний фон' },
];

export const COMPOSER_FG_PALETTE = [
    { key: 'blue', label: 'Синій текст' },
    { key: 'emerald', label: 'Зелений текст' },
    { key: 'rose', label: 'Малиновий текст' },
    { key: 'violet', label: 'Фіолетовий текст' },
    { key: 'amber', label: 'Бурштиновий текст' },
    { key: 'slate', label: 'Графітовий текст' },
];

/**
 * Тіло поля `style` для POST повідомлення (лише якщо є хоч щось).
 * @param {ComposerStyle} s
 * @returns {{ bold: boolean, italic: boolean, underline: boolean, bg?: string, fg?: string }|null}
 */
export function buildStylePayloadForApi(s) {
    const o = {
        bold: !!s.bold,
        italic: !!s.italic,
        underline: !!s.underline,
    };
    if (s.bg) {
        o.bg = s.bg;
    }
    if (s.fg) {
        o.fg = s.fg;
    }
    if (!o.bold && !o.italic && !o.underline && !s.bg && !s.fg) {
        return null;
    }

    return o;
}

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

/**
 * Inline-стиль кольору ніка в стрічці (ролі + стабільна палітра для звичайних користувачів).
 * @param {{ post_user?: string, post_color?: string }|null|undefined} m
 * @returns {Record<string, string>}
 */
export function nickColorStyleForPost(m) {
    if (!m || !m.post_user) {
        return {};
    }
    if (m.post_color === 'guest') {
        return { color: 'var(--rp-text-muted)' };
    }
    if (m.post_color === 'vip') {
        return { color: '#c2410c' };
    }
    if (m.post_color === 'mod') {
        return { color: '#15803d' };
    }
    if (m.post_color === 'admin') {
        return { color: 'var(--rp-chat-role-admin)' };
    }
    if (m.post_color === 'system' || m.type === 'system') {
        return { color: 'var(--rp-chat-nick-system)' };
    }
    /* Темні відтінки ≥ ~4.5:1 на білому для жирного ~15px (WCAG AA) */
    const palette = [
        '#9a3412',
        '#c2410c',
        '#1e3a8a',
        '#115e59',
        '#5b21b6',
        '#991b1b',
        '#155e75',
    ];
    let h = 0;
    const n = m.post_user;
    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 5) - h);
    }

    return { color: palette[Math.abs(h) % palette.length] };
}
