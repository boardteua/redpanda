/**
 * Парсинг plain-text тіла повідомлення для T46: URL → посилання / прев’ю картинки / дозволені ембеди.
 * Текст не інтерпретується як HTML; споживач рендерить сегменти без v-html для type=text.
 */

const URL_RE = /https?:\/\/[^\s<>"']+/gi;

/** Символи, що часто «чіпляються» до URL у тексті (дужки, пунктуація). */
const URL_TRAILING_JUNK = new Set([')', '.', ',', ';', '!', '?', ']', '"', "'", '»', '…']);

/**
 * @param {string} raw
 * @returns {string}
 */
export function trimUrlTrailing(raw) {
    if (!raw || !raw.startsWith('http')) {
        return raw;
    }
    let t = raw;
    while (t.length > 1) {
        let ok = false;
        try {
            const u = new URL(t);
            ok = u.protocol === 'http:' || u.protocol === 'https:';
        } catch {
            ok = false;
        }
        if (!ok) {
            t = t.slice(0, -1);
            continue;
        }
        const last = t[t.length - 1];
        if (URL_TRAILING_JUNK.has(last)) {
            t = t.slice(0, -1);
            continue;
        }
        return t;
    }
    return raw;
}

/**
 * @param {string} href
 * @returns {boolean}
 */
export function isSafeHttpUrl(href) {
    try {
        const u = new URL(href);
        if (u.protocol !== 'http:' && u.protocol !== 'https:') {
            return false;
        }
        if (u.username || u.password) {
            return false;
        }
        return true;
    } catch {
        return false;
    }
}

/**
 * @param {string} href
 * @returns {boolean}
 */
export function isLikelyImageUrl(href) {
    try {
        const u = new URL(href);
        const path = u.pathname.toLowerCase();
        return /\.(png|jpe?g|gif|webp|avif)(\?.*)?$/i.test(path);
    } catch {
        return false;
    }
}

/**
 * @param {string} url
 * @returns {string|null}
 */
export function youtubeVideoId(url) {
    try {
        const u = new URL(url);
        const host = u.hostname.replace(/^www\./, '');
        if (host === 'youtu.be') {
            const id = u.pathname.split('/').filter(Boolean)[0];
            return id || null;
        }
        if (host === 'youtube.com' || host === 'm.youtube.com' || host === 'music.youtube.com') {
            if (u.pathname.startsWith('/watch')) {
                return u.searchParams.get('v');
            }
            if (u.pathname.startsWith('/embed/')) {
                return u.pathname.split('/')[2] || null;
            }
            if (u.pathname.startsWith('/shorts/')) {
                return u.pathname.split('/')[2] || null;
            }
            if (host === 'music.youtube.com' && u.pathname.startsWith('/watch')) {
                return u.searchParams.get('v');
            }
        }
        return null;
    } catch {
        return null;
    }
}

/**
 * @param {string} trimmed
 * @returns {string|null}
 */
function youtubeIframeSrc(trimmed) {
    const id = youtubeVideoId(trimmed);
    if (!id || !/^[a-zA-Z0-9_-]{6,32}$/.test(id)) {
        return null;
    }
    return `https://www.youtube-nocookie.com/embed/${encodeURIComponent(id)}?rel=0`;
}

/**
 * @param {string} url
 * @returns {string|null}
 */
export function spotifyEmbedUrl(url) {
    try {
        const u = new URL(url);
        if (!u.hostname.endsWith('open.spotify.com')) {
            return null;
        }
        let path = u.pathname.replace(/^\/intl-[a-z]{2}\//, '/');
        const m = path.match(/^\/(track|album|playlist|episode|show)\/([a-zA-Z0-9]+)/);
        if (!m) {
            return null;
        }
        return `https://open.spotify.com/embed/${m[1]}/${m[2]}${u.search || ''}`;
    } catch {
        return null;
    }
}

/**
 * @param {string} url
 * @returns {string|null}
 */
function appleEmbedSrc(url) {
    try {
        const u = new URL(url);
        if (u.protocol !== 'https:' || u.hostname !== 'embed.music.apple.com') {
            return null;
        }
        return u.href.split('#')[0];
    } catch {
        return null;
    }
}

/**
 * @param {string} trimmed
 * @returns {{ kind: 'embed', iframeSrc: string, provider: string } | { kind: 'image' } | { kind: 'link' }}
 */
export function classifyUrl(trimmed) {
    const yt = youtubeIframeSrc(trimmed);
    if (yt) {
        return { kind: 'embed', iframeSrc: yt, provider: 'youtube' };
    }
    const sp = spotifyEmbedUrl(trimmed);
    if (sp) {
        return { kind: 'embed', iframeSrc: sp, provider: 'spotify' };
    }
    const ap = appleEmbedSrc(trimmed);
    if (ap) {
        return { kind: 'embed', iframeSrc: ap, provider: 'apple' };
    }
    if (isLikelyImageUrl(trimmed)) {
        return { kind: 'image' };
    }
    return { kind: 'link' };
}

/**
 * @param {string|null|undefined} text
 * @returns {Array<{ type: 'text', value: string } | { type: 'link', href: string, label: string } | { type: 'image', src: string, alt: string } | { type: 'embed', src: string, provider: string }>}
 */
export function parseChatMessageBody(text) {
    if (text == null || text === '') {
        return [{ type: 'text', value: '' }];
    }
    const str = String(text);
    const segments = [];
    let cursor = 0;
    const re = new RegExp(URL_RE.source, URL_RE.flags);
    let m;
    while ((m = re.exec(str)) !== null) {
        const matchStart = m.index;
        const fullMatch = m[0];
        if (matchStart > cursor) {
            segments.push({ type: 'text', value: str.slice(cursor, matchStart) });
        }
        const trimmed = trimUrlTrailing(fullMatch);
        if (!trimmed || !isSafeHttpUrl(trimmed)) {
            segments.push({ type: 'text', value: fullMatch });
            cursor = matchStart + fullMatch.length;
            continue;
        }
        const classified = classifyUrl(trimmed);
        if (classified.kind === 'embed') {
            segments.push({
                type: 'embed',
                src: classified.iframeSrc,
                provider: classified.provider,
            });
        } else if (classified.kind === 'image') {
            segments.push({
                type: 'image',
                src: trimmed,
                alt: 'Зображення за посиланням',
            });
        } else {
            segments.push({ type: 'link', href: trimmed, label: trimmed });
        }
        const suffix = fullMatch.slice(trimmed.length);
        if (suffix) {
            segments.push({ type: 'text', value: suffix });
        }
        cursor = matchStart + fullMatch.length;
    }
    if (cursor < str.length) {
        segments.push({ type: 'text', value: str.slice(cursor) });
    }
    if (segments.length === 0) {
        return [{ type: 'text', value: str }];
    }
    return mergeAdjacentTextSegments(segments);
}

/**
 * @param {Array<{ type: string, value?: string }>} segments
 * @returns {typeof segments}
 */
function mergeAdjacentTextSegments(segments) {
    const out = [];
    for (const s of segments) {
        if (s.type === 'text' && out.length && out[out.length - 1].type === 'text') {
            out[out.length - 1].value += s.value;
        } else {
            out.push(s);
        }
    }
    return out;
}
