/**
 * Парсинг plain-text тіла повідомлення (T46+): URL → посилання / прев’ю картинки / **.mp4** (inline `<video>`) / дозволені ембеди.
 * Текст не інтерпретується як HTML; споживач рендерить сегменти без v-html для type=text.
 *
 * Масштабованість: нові соцмережі — додати запис у {@link EMBED_RESOLVERS} (порядок = пріоритет).
 * Для частини хостів (Vimeo, SoundCloud, Threads permalink, …) без локального резолвера — сегмент `oembedPending`;
 * клієнт викликає GET `/api/v1/oembed` (T55) і підставляє iframe або rich Threads (blockquote + embed.js) з санітизованого `html` (T118).
 */

const URL_RE = /https?:\/\/[^\s<>"']+/gi;

/** Глобальний індекс `:code:` → безпечне ім’я файлу в /emoticon/ (T63). */
let chatEmoticonFilenameByCode = null;

/**
 * @param {Record<string, string>|null|undefined} map Ключ — code у нижньому регістрі.
 */
export function setChatEmoticonIndex(map) {
    if (map && typeof map === 'object' && Object.keys(map).length > 0) {
        chatEmoticonFilenameByCode = { ...map };
    } else {
        chatEmoticonFilenameByCode = null;
    }
}

/**
 * @returns {Record<string, string>|null}
 */
export function getChatEmoticonIndex() {
    return chatEmoticonFilenameByCode;
}

/**
 * @param {string} name
 * @returns {boolean}
 */
export function isSafeEmoticonFilename(name) {
    if (!name || typeof name !== 'string') {
        return false;
    }
    if (name.includes('..') || name.includes('/') || name.includes('\\')) {
        return false;
    }

    return /^[a-zA-Z0-9_.-]+$/.test(name);
}

/** Символи, що часто «чіпляються» до URL у тексті (дужки, пунктуація). */
const URL_TRAILING_JUNK = new Set([')', '.', ',', ';', '!', '?', ']', '"', "'", '»', '…']);

/**
 * @typedef {{ kind: 'embed', iframeSrc: string, provider: string }} EmbedClassification
 * @typedef {{ kind: 'image' } | { kind: 'link' } | { kind: 'inlineVideo' } | EmbedClassification} UrlClassification
 */

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
/**
 * Permalink публічного поста Threads (офіційний oEmbed через graph.threads.net, T118).
 * @param {string} trimmed
 * @returns {boolean}
 */
export function isThreadsPostOembedUrl(trimmed) {
    try {
        const u = new URL(trimmed);
        const host = u.hostname.replace(/^www\./, '').toLowerCase();
        if (host !== 'threads.net' && host !== 'threads.com') {
            return false;
        }
        const path = u.pathname;
        return /\/@[\w.]+\/post\/[^/]+/.test(path) || /^\/t\/[^/]+/.test(path);
    } catch {
        return false;
    }
}

/**
 * Чи варто питати бекенд oEmbed (обмежений список хостів, без дублювання жорстких ембедів).
 * @param {string} trimmed
 * @returns {boolean}
 */
export function shouldTryBackendOembedUrl(trimmed) {
    try {
        const u = new URL(trimmed);
        const h = u.hostname.replace(/^www\./, '').toLowerCase();
        if (isThreadsPostOembedUrl(trimmed)) {
            return true;
        }
        if (h === 'player.vimeo.com') {
            return true;
        }
        if (h === 'vimeo.com' || h.endsWith('.vimeo.com')) {
            return true;
        }
        if (h.includes('dailymotion.com')) {
            return true;
        }
        if (h === 'soundcloud.com' || h === 'on.soundcloud.com') {
            return true;
        }
        if (h === 'tiktok.com' || h.endsWith('.tiktok.com')) {
            return true;
        }
        if (h === 'twitch.tv' || h.endsWith('.twitch.tv')) {
            return true;
        }
        return false;
    } catch {
        return false;
    }
}

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
 * Пряме посилання на файл **.mp4** (T64). Той самий рівень перевірки шляху, що й для image URL.
 * @param {string} href
 * @returns {boolean}
 */
export function isLikelyMp4Url(href) {
    try {
        const u = new URL(href);
        const path = u.pathname.toLowerCase();
        return /\.mp4(\?.*)?$/i.test(path);
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
 * X / Twitter: статус за числовим id.
 * @param {string} trimmed
 * @returns {EmbedClassification|null}
 */
export function tryTwitterStatusEmbed(trimmed) {
    try {
        const u = new URL(trimmed);
        const host = u.hostname.replace(/^www\./, '').toLowerCase();
        if (host !== 'twitter.com' && host !== 'x.com' && host !== 'mobile.twitter.com' && host !== 'mobile.x.com') {
            return null;
        }
        const path = u.pathname;
        let m = path.match(/\/status(?:es)?\/(\d{10,25})\b/);
        if (!m) {
            m = path.match(/\/i\/web\/status\/(\d{10,25})\b/);
        }
        if (!m) {
            return null;
        }
        const id = m[1];
        return {
            kind: 'embed',
            iframeSrc: `https://platform.twitter.com/embed/Tweet.html?id=${encodeURIComponent(id)}`,
            provider: 'twitter',
        };
    } catch {
        return null;
    }
}

/**
 * Публічний пост каналу/бота: t.me/name/123 → iframe з ?embed=1
 * @param {string} trimmed
 * @returns {EmbedClassification|null}
 */
export function tryTelegramPostEmbed(trimmed) {
    try {
        const u = new URL(trimmed);
        const host = u.hostname.replace(/^www\./, '').toLowerCase();
        if (host !== 't.me' && host !== 'telegram.me') {
            return null;
        }
        const parts = u.pathname.split('/').filter(Boolean);
        if (parts.length !== 2) {
            return null;
        }
        const [slug, msgId] = parts;
        const reserved = new Set([
            's',
            '+',
            'joinchat',
            'iv',
            'addstickers',
            'c',
            'login',
            'proxy',
            'share',
        ]);
        if (reserved.has(slug.toLowerCase())) {
            return null;
        }
        if (!/^\d+$/.test(msgId)) {
            return null;
        }
        if (!/^[\w\d_]{3,64}$/i.test(slug)) {
            return null;
        }
        return {
            kind: 'embed',
            iframeSrc: `https://t.me/${encodeURIComponent(slug)}/${encodeURIComponent(msgId)}?embed=1`,
            provider: 'telegram',
        };
    } catch {
        return null;
    }
}

/**
 * Facebook / fb.me: офіційний post plugin (href у query).
 * @param {string} trimmed
 * @returns {EmbedClassification|null}
 */
export function tryFacebookPostEmbed(trimmed) {
    try {
        const u = new URL(trimmed);
        const h = u.hostname.toLowerCase();
        const isFacebook =
            h === 'fb.me' || h.endsWith('.facebook.com') || h === 'facebook.com';
        if (!isFacebook) {
            return null;
        }
        const p = u.pathname;
        const q = u.searchParams;
        const looksLikePost =
            q.has('story_fbid') ||
            /\/posts\/[^/]+/.test(p) ||
            p.includes('/permalink.php') ||
            p.includes('/story.php') ||
            p.includes('/photo.php') ||
            /\/videos\/\d/.test(p) ||
            /\/reel\//.test(p) ||
            /\/watch\//.test(p) ||
            /\/groups\/[^/]+\/permalink\//.test(p) ||
            (h === 'fb.me' && p.length > 1);
        if (!looksLikePost) {
            return null;
        }
        const href = encodeURIComponent(u.href.split('#')[0]);
        return {
            kind: 'embed',
            iframeSrc: `https://www.facebook.com/plugins/post.php?href=${href}&show_text=true&width=500`,
            provider: 'facebook',
        };
    } catch {
        return null;
    }
}

/**
 * Резолвери ембедів (перший успішний виграє). Додавайте нові сюди — без зміни `classifyUrl`.
 * @type {ReadonlyArray<{ id: string, resolve: (trimmed: string) => EmbedClassification | null }>}
 */
export const EMBED_RESOLVERS = Object.freeze([
    {
        id: 'youtube',
        resolve(trimmed) {
            const src = youtubeIframeSrc(trimmed);
            return src ? { kind: 'embed', iframeSrc: src, provider: 'youtube' } : null;
        },
    },
    {
        id: 'spotify',
        resolve(trimmed) {
            const src = spotifyEmbedUrl(trimmed);
            return src ? { kind: 'embed', iframeSrc: src, provider: 'spotify' } : null;
        },
    },
    {
        id: 'apple_music',
        resolve(trimmed) {
            const src = appleEmbedSrc(trimmed);
            return src ? { kind: 'embed', iframeSrc: src, provider: 'apple' } : null;
        },
    },
    {
        id: 'twitter',
        resolve: tryTwitterStatusEmbed,
    },
    {
        id: 'telegram',
        resolve: tryTelegramPostEmbed,
    },
    {
        id: 'facebook',
        resolve: tryFacebookPostEmbed,
    },
]);

/**
 * @param {string} trimmed
 * @returns {UrlClassification}
 */
export function classifyUrl(trimmed) {
    for (let i = 0; i < EMBED_RESOLVERS.length; i += 1) {
        const hit = EMBED_RESOLVERS[i].resolve(trimmed);
        if (hit) {
            return hit;
        }
    }
    if (isLikelyMp4Url(trimmed)) {
        return { kind: 'inlineVideo' };
    }
    if (isLikelyImageUrl(trimmed)) {
        return { kind: 'image' };
    }
    return { kind: 'link' };
}

/**
 * Лише URL / ембеди / зображення (без `:code:` смайлів).
 *
 * @param {string} str
 * @returns {Array<{ type: 'text', value: string } | { type: 'link', href: string, label: string } | { type: 'image', src: string, alt: string } | { type: 'inlineVideo', src: string } | { type: 'embed', src: string, provider: string } | { type: 'oembedPending', href: string, label: string }>}
 */
function parseChatMessageBodyUrlsOnly(str) {
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
        } else if (classified.kind === 'link' && shouldTryBackendOembedUrl(trimmed)) {
            segments.push({
                type: 'oembedPending',
                href: trimmed,
                label: trimmed,
            });
        } else if (classified.kind === 'inlineVideo') {
            segments.push({
                type: 'inlineVideo',
                src: trimmed,
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
 * Розбити текст на фрагменти з відомими `:code:` смайлами (T63).
 *
 * @param {string} str
 * @param {Record<string, string>|null} index
 * @returns {Array<{ type: 'text', value: string } | { type: 'emoticon', code: string, src: string }>}
 */
function splitEmoticonPieces(str, index) {
    if (!index || Object.keys(index).length === 0) {
        return [{ type: 'text', value: str }];
    }
    const out = [];
    let pos = 0;
    const len = str.length;
    while (pos < len) {
        const colon = str.indexOf(':', pos);
        if (colon === -1) {
            out.push({ type: 'text', value: str.slice(pos) });
            break;
        }
        const endColon = str.indexOf(':', colon + 1);
        if (endColon === -1) {
            out.push({ type: 'text', value: str.slice(pos) });
            break;
        }
        if (colon > pos) {
            out.push({ type: 'text', value: str.slice(pos, colon) });
        }
        const inner = str.slice(colon + 1, endColon);
        let filename = null;
        if (inner.length >= 1 && inner.length <= 64 && /^[a-zA-Z0-9_]+$/.test(inner)) {
            filename = index[inner.toLowerCase()] ?? null;
        }
        if (filename && isSafeEmoticonFilename(filename)) {
            out.push({
                type: 'emoticon',
                code: inner,
                src: `/emoticon/${filename}`,
            });
            pos = endColon + 1;
        } else {
            out.push({ type: 'text', value: str.slice(colon, colon + 1) });
            pos = colon + 1;
        }
    }

    return mergeAdjacentTextSegments(out);
}

/**
 * @param {string|null|undefined} text
 * @param {{ emoticonIndex?: Record<string, string>|null }|undefined} options
 * @returns {Array<{ type: 'text', value: string } | { type: 'link', href: string, label: string } | { type: 'image', src: string, alt: string } | { type: 'inlineVideo', src: string } | { type: 'embed', src: string, provider: string } | { type: 'oembedPending', href: string, label: string } | { type: 'emoticon', code: string, src: string }>}
 */
export function parseChatMessageBody(text, options) {
    if (text == null || text === '') {
        return [{ type: 'text', value: '' }];
    }
    const str = String(text);
    const index =
        options && Object.prototype.hasOwnProperty.call(options, 'emoticonIndex')
            ? options.emoticonIndex
            : chatEmoticonFilenameByCode;
    const emotParts = splitEmoticonPieces(str, index && Object.keys(index).length ? index : null);
    /** @type {ReturnType<typeof parseChatMessageBody>} */
    const segments = [];
    for (let i = 0; i < emotParts.length; i += 1) {
        const part = emotParts[i];
        if (part.type === 'emoticon') {
            segments.push(part);
        } else {
            segments.push(...parseChatMessageBodyUrlsOnly(part.value));
        }
    }

    return mergeAdjacentTextSegments(segments);
}

/**
 * Чи є в повідомленні блоковий медіавміст (ембед, oEmbed, inline-зображення).
 * Потрібно для layout у стрічці: `inline-block` ламає висоту aspect-ratio / iframe без інтринсичної ширини.
 *
 * @param {string|null|undefined} text
 * @returns {boolean}
 */
export function messageHasBlockMedia(text) {
    const segs = parseChatMessageBody(text);
    return segs.some(
        (s) =>
            s.type === 'embed' ||
            s.type === 'oembedPending' ||
            s.type === 'image' ||
            s.type === 'inlineVideo',
    );
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
