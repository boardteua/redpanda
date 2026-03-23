/**
 * T65: бейдж непрочитаних приватів на favicon через [favicon-badge-notify](https://github.com/jsdeveloperr/favicon-badge-notify).
 * Базова іконка — `public/board-te-ua-favicon.ico` (спаршено з legacy `<link rel="shortcut icon" href="favicon.ico">` на https://www.board.te.ua/ ).
 */

import faviconBadgeNotifyFactory from 'favicon-badge-notify';

const FAVICON_PUBLIC_PATH = '/board-te-ua-favicon.ico';

/** UMD interop */
const createFaviconBadgeNotify =
    typeof faviconBadgeNotifyFactory === 'function'
        ? faviconBadgeNotifyFactory
        : faviconBadgeNotifyFactory?.default;

let srcNonce = 0;
/** @type {{ drawBadge: (v?: string|number) => Promise<string>, destroyBadge: () => void } | null} */
let badgeApi = null;
/** Послідовне застосування (без гонок між WS і loadConversations). */
let queue = Promise.resolve();

function ensureIconLink() {
    let link = document.querySelector('link[rel*="icon"]');
    if (!link) {
        link = document.createElement('link');
        link.rel = 'icon';
        document.head.appendChild(link);
    }

    return link;
}

function formatBadgeCount(n) {
    if (n > 99) {
        return '99+';
    }

    return String(n);
}

async function applyPrivateUnreadBadge(count) {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }
    if (typeof createFaviconBadgeNotify !== 'function') {
        return;
    }

    const n = Math.max(0, Math.floor(Number(count) || 0));
    const link = ensureIconLink();

    if (badgeApi) {
        try {
            badgeApi.destroyBadge();
        } catch {
            /* */
        }
        badgeApi = null;
    }

    if (n <= 0) {
        const u = new URL(FAVICON_PUBLIC_PATH, window.location.origin);
        u.searchParams.set('t', String(Date.now()));
        link.href = u.href;

        return;
    }

    const srcUrl = new URL(FAVICON_PUBLIC_PATH, window.location.origin);
    srcUrl.searchParams.set('rp', String(++srcNonce));
    srcUrl.searchParams.set('t', String(Date.now()));

    badgeApi = createFaviconBadgeNotify({
        src: srcUrl.href,
        backgroundColor: '#dc2626',
        textColor: '#ffffff',
    });

    try {
        const href = await badgeApi.drawBadge(formatBadgeCount(n));
        link.href = href;
    } catch {
        const u = new URL(FAVICON_PUBLIC_PATH, window.location.origin);
        u.searchParams.set('t', String(Date.now()));
        link.href = u.href;
    } finally {
        if (badgeApi) {
            try {
                badgeApi.destroyBadge();
            } catch {
                /* */
            }
            badgeApi = null;
        }
    }
}

/**
 * @param {number} count
 */
export function setFaviconPrivateUnreadBadge(count) {
    queue = queue
        .then(() => applyPrivateUnreadBadge(count))
        .catch(() => {});

    return queue;
}

export function resetFaviconPrivateUnreadBadge() {
    setFaviconPrivateUnreadBadge(0);
}
