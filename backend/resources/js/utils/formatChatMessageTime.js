/**
 * Час повідомлень чату: API дає Unix-секунди (`post_date`, `sent_at`) як UTC-instant.
 * Відображення — у локальній часовій зоні та локалі браузера (T121).
 */

/**
 * @param {unknown} unixSeconds
 * @returns {Date|null}
 */
export function dateFromUnixSeconds(unixSeconds) {
    const n = Number(unixSeconds);
    if (!Number.isFinite(n) || n <= 0) {
        return null;
    }
    return new Date(n * 1000);
}

/**
 * ISO 8601 (UTC) для атрибута `datetime` у `<time>`.
 * @param {unknown} unixSeconds
 * @returns {string}
 */
export function isoUtcFromUnixSeconds(unixSeconds) {
    const d = dateFromUnixSeconds(unixSeconds);

    return d ? d.toISOString() : '';
}

/**
 * Година:хвилина у локальному часі (стрічка кімнати, приват).
 * `locales === undefined` — дефолтна локаль браузера (MDN Intl).
 * @param {unknown} unixSeconds
 * @returns {string}
 */
export function formatChatMessageTimeLocal(unixSeconds) {
    const d = dateFromUnixSeconds(unixSeconds);
    if (!d) {
        return '';
    }
    try {
        return new Intl.DateTimeFormat(undefined, {
            hour: 'numeric',
            minute: '2-digit',
        }).format(d);
    } catch {
        try {
            return d.toLocaleTimeString(undefined, {
                hour: 'numeric',
                minute: '2-digit',
            });
        } catch {
            return '';
        }
    }
}

/**
 * Дата + час для архіву та подібних списків.
 * @param {unknown} unixSeconds
 * @returns {string}
 */
export function formatChatArchiveDateTimeLocal(unixSeconds) {
    const d = dateFromUnixSeconds(unixSeconds);
    if (!d) {
        return '';
    }
    try {
        return new Intl.DateTimeFormat(undefined, {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: 'numeric',
            minute: '2-digit',
        }).format(d);
    } catch {
        try {
            return d.toLocaleString(undefined, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: 'numeric',
                minute: '2-digit',
            });
        } catch {
            return '';
        }
    }
}
