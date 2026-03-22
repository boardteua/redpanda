/**
 * Клієнт для T55 GET /api/v1/oembed (Sanctum cookie). Успішні відповіді кешуються; паралельні запити дедупляться.
 */

/** @type {Map<string, Promise<Record<string, unknown>>>} */
const inFlight = new Map();

/** @type {Map<string, Record<string, unknown>>} */
const successCache = new Map();

const MAX_CACHE = 64;

/**
 * @param {string} resourceUrl
 * @param {{ signal?: AbortSignal }} [opts]
 * @returns {Promise<Record<string, unknown>>}
 */
export function fetchOembed(resourceUrl, opts = {}) {
    const key = resourceUrl;
    const cached = successCache.get(key);
    if (cached) {
        return Promise.resolve(cached);
    }

    let p = inFlight.get(key);
    if (!p) {
        p = window.axios
            .get('/api/v1/oembed', {
                params: { url: resourceUrl },
                signal: opts.signal,
            })
            .then((r) => {
                const data = r.data && typeof r.data === 'object' ? r.data : {};
                trimSuccessCache();
                successCache.set(key, data);
                return data;
            })
            .finally(() => {
                inFlight.delete(key);
            });
        inFlight.set(key, p);
    }
    return p;
}

function trimSuccessCache() {
    if (successCache.size <= MAX_CACHE) {
        return;
    }
    const first = successCache.keys().next().value;
    if (first !== undefined) {
        successCache.delete(first);
    }
}

/**
 * Витягнути атрибути iframe з санітизованого HTML бекенду (без v-html).
 * @param {string} html
 * @returns {{ src: string, width?: string, height?: string, allow?: string, title?: string } | null}
 */
export function parseIframeFromOembedHtml(html) {
    if (!html || typeof html !== 'string') {
        return null;
    }
    try {
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const el = doc.querySelector('iframe');
        if (!el) {
            return null;
        }
        const src = el.getAttribute('src');
        if (!src || !src.startsWith('https://')) {
            return null;
        }
        return {
            src,
            width: el.getAttribute('width') || undefined,
            height: el.getAttribute('height') || undefined,
            allow: el.getAttribute('allow') || undefined,
            title: el.getAttribute('title') || undefined,
        };
    } catch {
        return null;
    }
}
