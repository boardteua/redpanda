/**
 * T65: бейдж непрочитаних приватів на favicon (canvas), без зовнішніх залежностей.
 */

/** Базова іконка до будь-яких blob/data (щоб після «0 непрочитаних» відновлювалась саме вона). */
let cachedDefaultHref = null;
let lastObjectUrl = null;

function isUsableIconHref(href) {
    if (!href || typeof href !== 'string') {
        return false;
    }
    const h = href.trim().toLowerCase();

    return h.startsWith('http://')
        || h.startsWith('https://')
        || h.startsWith('/');
}

/**
 * Перша валідна статична іконка в документі або /favicon.ico (не blob/data і не «порожній» href → URL сторінки).
 */
function resolveDefaultIconHref() {
    if (cachedDefaultHref) {
        return cachedDefaultHref;
    }
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return '';
    }
    const origin = window.location.origin;
    const links = document.querySelectorAll('link[rel*="icon"]');
    for (let i = 0; i < links.length; i += 1) {
        const raw = links[i].getAttribute('href');
        if (!raw || raw.startsWith('blob:') || raw.startsWith('data:')) {
            continue;
        }
        try {
            const abs = new URL(raw, origin).href;
            if (abs.startsWith('blob:') || abs.startsWith('data:')) {
                continue;
            }
            if (!isUsableIconHref(abs)) {
                continue;
            }
            const path = new URL(abs).pathname || '';
            if (path && path !== '/' && !/\.(ico|png|svg|gif|webp|jpg|jpeg)$/i.test(path)) {
                continue;
            }
            cachedDefaultHref = abs;

            return cachedDefaultHref;
        } catch {
            /* */
        }
    }
    cachedDefaultHref = `${origin}/favicon.ico`;

    return cachedDefaultHref;
}

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

/**
 * @param {number} count
 */
export function setFaviconPrivateUnreadBadge(count) {
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return;
    }
    const n = Math.max(0, Number(count) || 0);
    const link = ensureIconLink();

    if (n <= 0) {
        if (lastObjectUrl) {
            URL.revokeObjectURL(lastObjectUrl);
            lastObjectUrl = null;
        }
        const base = resolveDefaultIconHref();
        try {
            const u = new URL(base, window.location.origin);
            u.searchParams.set('rp_badge', '0');
            u.searchParams.set('t', String(Date.now()));
            link.href = u.toString();
        } catch {
            link.href = `${base}${base.includes('?') ? '&' : '?'}rp_badge=0&t=${Date.now()}`;
        }

        return;
    }

    resolveDefaultIconHref();

    const size = 32;
    const canvas = document.createElement('canvas');
    canvas.width = size;
    canvas.height = size;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }

    const img = new Image();
    img.onload = () => {
        try {
            ctx.clearRect(0, 0, size, size);
            ctx.drawImage(img, 0, 0, size, size);
        } catch {
            ctx.fillStyle = '#1f2937';
            ctx.fillRect(0, 0, size, size);
        }

        const badgeR = 9;
        const cx = size - badgeR - 2;
        const cy = size - badgeR - 2;
        ctx.fillStyle = '#dc2626';
        ctx.beginPath();
        ctx.arc(cx, cy, badgeR, 0, Math.PI * 2);
        ctx.fill();

        const label = formatBadgeCount(n);
        ctx.fillStyle = '#ffffff';
        ctx.font = label.length > 2 ? 'bold 7px sans-serif' : 'bold 10px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(label, cx, cy + 0.5);

        if (lastObjectUrl) {
            URL.revokeObjectURL(lastObjectUrl);
            lastObjectUrl = null;
        }
        canvas.toBlob((blob) => {
            if (!blob) {
                return;
            }
            lastObjectUrl = URL.createObjectURL(blob);
            link.href = lastObjectUrl;
        }, 'image/png');
    };
    img.onerror = () => {
        ctx.fillStyle = '#1f2937';
        ctx.fillRect(0, 0, size, size);
        const badgeR = 9;
        const cx = size - badgeR - 2;
        const cy = size - badgeR - 2;
        ctx.fillStyle = '#dc2626';
        ctx.beginPath();
        ctx.arc(cx, cy, badgeR, 0, Math.PI * 2);
        ctx.fill();
        ctx.fillStyle = '#ffffff';
        ctx.font = 'bold 10px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(formatBadgeCount(n), cx, cy + 0.5);
        if (lastObjectUrl) {
            URL.revokeObjectURL(lastObjectUrl);
            lastObjectUrl = null;
        }
        canvas.toBlob((blob) => {
            if (!blob) {
                return;
            }
            lastObjectUrl = URL.createObjectURL(blob);
            link.href = lastObjectUrl;
        }, 'image/png');
    };
    img.src = resolveDefaultIconHref();
}

export function resetFaviconPrivateUnreadBadge() {
    setFaviconPrivateUnreadBadge(0);
}
