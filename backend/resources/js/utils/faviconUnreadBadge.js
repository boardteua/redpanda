/**
 * T65: бейдж непрочитаних приватів на favicon (canvas), без зовнішніх залежностей.
 */

let cachedDefaultHref = null;
let lastDataUrl = null;

function resolveDefaultIconHref() {
    if (cachedDefaultHref) {
        return cachedDefaultHref;
    }
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        return '';
    }
    const el = document.querySelector('link[rel*="icon"]');
    if (el && el.href) {
        cachedDefaultHref = el.href;

        return cachedDefaultHref;
    }
    cachedDefaultHref = `${window.location.origin}/favicon.ico`;

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
        if (lastDataUrl) {
            URL.revokeObjectURL(lastDataUrl);
            lastDataUrl = null;
        }
        link.href = resolveDefaultIconHref();

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

        if (lastDataUrl) {
            URL.revokeObjectURL(lastDataUrl);
            lastDataUrl = null;
        }
        canvas.toBlob((blob) => {
            if (!blob) {
                return;
            }
            lastDataUrl = URL.createObjectURL(blob);
            link.href = lastDataUrl;
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
        if (lastDataUrl) {
            URL.revokeObjectURL(lastDataUrl);
            lastDataUrl = null;
        }
        canvas.toBlob((blob) => {
            if (!blob) {
                return;
            }
            lastDataUrl = URL.createObjectURL(blob);
            link.href = lastDataUrl;
        }, 'image/png');
    };
    img.src = resolveDefaultIconHref();
}

export function resetFaviconPrivateUnreadBadge() {
    setFaviconPrivateUnreadBadge(0);
}
