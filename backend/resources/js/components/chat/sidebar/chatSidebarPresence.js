/** Pure UI helpers for sidebar presence rows (T104, shared by Users / Friends panels). */

/** T126: до підтвердженого мапінгу з REST — нейтральний рядок, без «хибного онлайн». */
export const PRESENCE_STATUS_UNKNOWN = 'unknown';

export function normalizedPresenceStatus(raw) {
    if (raw === 'away' || raw === 'inactive') {
        return raw;
    }
    if (raw === PRESENCE_STATUS_UNKNOWN) {
        return PRESENCE_STATUS_UNKNOWN;
    }

    return 'online';
}

export function presenceRowClass(status) {
    const s = normalizedPresenceStatus(status);
    /** Невизначений статус — той самий візуал, що «неактивний»/офлайн (T126 follow-up). */
    if (s === 'inactive' || s === PRESENCE_STATUS_UNKNOWN) {
        return 'rp-presence-row--inactive';
    }
    if (s === 'away') {
        return 'rp-presence-row--away';
    }

    return '';
}

export function presenceDotClass(status) {
    const s = normalizedPresenceStatus(status);
    if (s === 'inactive' || s === PRESENCE_STATUS_UNKNOWN) {
        return 'bg-gray-500';
    }
    if (s === 'away') {
        return 'bg-amber-500';
    }

    return 'bg-green-600';
}

export function presenceLabelUa(status) {
    const s = normalizedPresenceStatus(status);
    if (s === 'away') {
        return 'Відійшов';
    }
    if (s === 'inactive') {
        return 'Неактивний';
    }
    if (s === PRESENCE_STATUS_UNKNOWN) {
        return 'Статус уточнюється';
    }

    return 'Онлайн';
}

export function sexGlyphAndLabel(sex) {
    if (sex === 'male') {
        return { glyph: '\u2642', label: 'Чоловік' };
    }
    if (sex === 'female') {
        return { glyph: '\u2640', label: 'Жінка' };
    }
    if (sex === 'other') {
        return { glyph: '\u26a7', label: 'Інше' };
    }

    return null;
}

export function formatPrivateUnread(n) {
    const c = Number(n);
    if (!c || c < 1) {
        return '';
    }

    return c > 99 ? '99+' : String(c);
}
