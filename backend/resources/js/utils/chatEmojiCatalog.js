/**
 * Фільтрація каталогу смайлів для модалу композера (T33 / T63).
 * Елементи з API: `code`, `display_name`, `file`, `keywords`.
 *
 * @param {Array<{ code: string, display_name?: string, file?: string, keywords?: string }>} items
 * @param {string} q
 * @returns {typeof items}
 */
export function filterEmojiItems(items, q) {
    const list = Array.isArray(items) ? items : [];
    const s = (q || '').trim().toLowerCase();
    if (!s) {
        return [...list];
    }

    return list.filter((it) => {
        const code = String(it.code || '').toLowerCase();
        const title = String(it.display_name || '').toLowerCase();
        const kw = String(it.keywords || '').toLowerCase();
        if (code.includes(s) || title.includes(s)) {
            return true;
        }

        return kw
            .split(/\s+/)
            .some((w) => w && (w.includes(s) || s.includes(w)));
    });
}
