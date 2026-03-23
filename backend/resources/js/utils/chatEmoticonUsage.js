/**
 * Локальна статистика вибору смайлів із модалу (частота → сортування списку).
 * Лише цей браузер / профіль; без відправки на сервер.
 */
const STORAGE_KEY = 'redpanda-chat-emoticon-usage';

/**
 * @returns {Record<string, number>}
 */
export function getEmoticonUsageCounts() {
    if (typeof localStorage === 'undefined') {
        return {};
    }
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return {};
        }
        const o = JSON.parse(raw);
        if (typeof o !== 'object' || o === null || Array.isArray(o)) {
            return {};
        }
        /** @type {Record<string, number>} */
        const out = {};
        Object.keys(o).forEach((key) => {
            const n = Number(o[key]);
            if (key && Number.isFinite(n) && n > 0) {
                out[String(key).toLowerCase()] = Math.min(Math.floor(n), 1_000_000);
            }
        });

        return out;
    } catch {
        return {};
    }
}

/**
 * @param {string} code
 */
export function recordEmoticonUsage(code) {
    if (typeof localStorage === 'undefined') {
        return;
    }
    const k = String(code || '').trim().toLowerCase();
    if (!k) {
        return;
    }
    const map = getEmoticonUsageCounts();
    map[k] = (map[k] || 0) + 1;
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(map));
    } catch {
        // квота або приватний режим — ігноруємо
    }
}
