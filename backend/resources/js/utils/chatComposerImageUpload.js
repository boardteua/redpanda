/** Ліміт узгоджено з `ChatImageController` (max:4096 KB). */
export const CHAT_IMAGE_MAX_BYTES = 4 * 1024 * 1024;

export const CHAT_IMAGE_ALLOWED_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

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
    if (file.size > maxBytes) {
        return { ok: false, message: 'Файл завеликий (максимум 4 МБ).' };
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
