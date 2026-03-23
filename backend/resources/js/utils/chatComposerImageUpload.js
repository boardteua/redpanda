/** Фолбек, якщо `GET /api/v1/chat/settings` ще не завантажив `max_chat_image_upload_bytes`. */
export const CHAT_IMAGE_MAX_BYTES = 4 * 1024 * 1024;

export const CHAT_IMAGE_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

/**
 * Короткий підпис ліміту для title/повідомлень (укр.).
 *
 * @param {number} maxBytes
 * @returns {string}
 */
export function formatChatImageMaxLabel(maxBytes) {
    const n = Number(maxBytes);
    if (!Number.isFinite(n) || n <= 0) {
        return '4 МБ';
    }
    const mb = n / (1024 * 1024);
    if (mb >= 1) {
        const t = mb >= 10 ? mb.toFixed(0) : mb.toFixed(1).replace(/\.0$/, '');

        return `${t} МБ`;
    }
    const kb = Math.max(1, Math.round(n / 1024));

    return `${kb} КБ`;
}

/**
 * @param {File|null|undefined} file
 * @returns {{ ok: true } | { ok: false, message: string }}
 */
export function validateChatImageFileForUpload(file, maxBytes = CHAT_IMAGE_MAX_BYTES) {
    if (!file) {
        return { ok: false, message: '' };
    }
    if (!CHAT_IMAGE_ALLOWED_MIMES.includes(file.type)) {
        return { ok: false, message: 'Дозволені лише зображення JPEG, PNG, GIF або WebP.' };
    }
    const cap = Number(maxBytes);
    const limit = Number.isFinite(cap) && cap > 0 ? cap : CHAT_IMAGE_MAX_BYTES;
    if (file.size > limit) {
        return { ok: false, message: `Файл завеликий (максимум ${formatChatImageMaxLabel(limit)}).` };
    }

    return { ok: true };
}

/**
 * Перший файл-зображення з буфера (paste). Якщо немає — null (текстовий paste лишається стандартним).
 *
 * @param {DataTransfer|null|undefined} clipboardData
 * @returns {File|null}
 */
export function getFirstClipboardImageFile(clipboardData) {
    if (!clipboardData || !clipboardData.items) {
        return null;
    }
    const { items } = clipboardData;
    for (let i = 0; i < items.length; i += 1) {
        const item = items[i];
        if (item.kind !== 'file') {
            continue;
        }
        const t = item.type || '';
        if (!CHAT_IMAGE_ALLOWED_MIMES.includes(t)) {
            continue;
        }
        const f = item.getAsFile();
        if (f instanceof File) {
            return f;
        }
    }

    return null;
}
