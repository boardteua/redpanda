/**
 * Клієнт для T55 GET /api/v1/oembed (Sanctum cookie). Успішні відповіді кешуються; паралельні запити дедупляться.
 * Threads rich embed (T118): blockquote + https://www.threads.com/embed.js + instgrm.Embeds.process().
 */

const THREADS_EMBED_JS = 'https://www.threads.com/embed.js';

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
/**
 * @param {Record<string, unknown>} data
 * @param {string} resourceUrl
 * @returns {boolean}
 */
export function isThreadsRichOembedPayload(data, resourceUrl) {
    if (!data || typeof data !== 'object') {
        return false;
    }
    if (data.type !== 'rich') {
        return false;
    }
    const pn = typeof data.provider_name === 'string' ? data.provider_name.toLowerCase() : '';
    if (pn !== 'threads') {
        return false;
    }
    try {
        const h = new URL(resourceUrl).hostname.replace(/^www\./, '').toLowerCase();
        if (h !== 'threads.com' && h !== 'threads.net') {
            return false;
        }
    } catch {
        return false;
    }
    const html = typeof data.html === 'string' ? data.html : '';
    return html.includes('text-post-media');
}

/**
 * Один раз на сторінку: скрипт Meta для Threads/Instagram embeds.
 */
export function ensureThreadsEmbedScript() {
    if (typeof document === 'undefined') {
        return;
    }
    if (document.querySelector('script[data-rp-threads-embed]')) {
        return;
    }
    const s = document.createElement('script');
    s.src = THREADS_EMBED_JS;
    s.async = true;
    s.dataset.rpThreadsEmbed = '1';
    document.head.appendChild(s);
}

/**
 * Після динамічного вставлення blockquote (SPA).
 */
export function notifyThreadsEmbedsProcessed() {
    if (typeof window === 'undefined') {
        return;
    }
    try {
        const ig = window.instgrm;
        if (ig && ig.Embeds && typeof ig.Embeds.process === 'function') {
            ig.Embeds.process();
        }
    } catch {
        /* ignore */
    }
}

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
