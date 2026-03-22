/**
 * Ініціали для fallback-аватарки (логіка на кшталт vue-avatar: слова → дві літери, одне слово → дві перші).
 *
 * @param {string|null|undefined} name
 * @returns {string}
 */
export function initialsFromDisplayName(name) {
    if (name == null || typeof name !== 'string') {
        return '?';
    }
    const p = name.trim();
    if (!p) {
        return '?';
    }
    const parts = p.split(/\s+/).filter(Boolean);
    const firstGrapheme = (s) => {
        const arr = Array.from(s);

        return arr[0] || '';
    };
    if (parts.length >= 2) {
        const a = firstGrapheme(parts[0]);
        const b = firstGrapheme(parts[parts.length - 1]);

        return (a + b).toUpperCase() || '?';
    }
    const g = Array.from(parts[0]);
    if (g.length >= 2) {
        return (g[0] + g[1]).toUpperCase();
    }

    return (g[0] || '?').toUpperCase();
}

/**
 * Стабільний насичений фон для ініціалів (достатній контраст із білим текстом ~14px).
 *
 * @param {string|null|undefined} name
 * @returns {string}
 */
export function avatarBackgroundFromName(name) {
    let h = 0;
    const s = String(name ?? '');
    for (let i = 0; i < s.length; i++) {
        h = s.charCodeAt(i) + ((h << 5) - h);
    }
    const hue = Math.abs(h) % 360;

    return `hsl(${hue} 42% 40%)`;
}
