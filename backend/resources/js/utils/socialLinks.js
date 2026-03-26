/**
 * @param {string} key
 * @param {string} raw
 * @returns {string|null} safe http(s) URL or null
 */
export function normalizeSocialHref(key, raw) {
    if (raw == null) {
        return null;
    }
    let s = String(raw).trim();
    if (!s) {
        return null;
    }
    if (/^(javascript|data|vbscript):/i.test(s)) {
        return null;
    }

    if (/^https?:\/\//i.test(s) || /^\/\//.test(s)) {
        const withScheme = /^\/\//.test(s) ? `https:${s}` : s;
        try {
            const u = new URL(withScheme);
            if (u.protocol !== 'http:' && u.protocol !== 'https:') {
                return null;
            }

            return u.href;
        } catch {
            return null;
        }
    }

    const stripAt = (v) => v.replace(/^@+/, '').trim();

    if (!/^[a-z][-a-z0-9+.]*:/i.test(s)) {
        if (key === 'telegram') {
            const h = stripAt(s);
            s = h ? `https://t.me/${encodeURIComponent(h)}` : '';
        } else if (key === 'twitter') {
            const h = stripAt(s);
            s = h ? `https://twitter.com/${encodeURIComponent(h)}` : '';
        } else if (key === 'tiktok') {
            const h = stripAt(s);
            s = h ? `https://www.tiktok.com/@${encodeURIComponent(h)}` : '';
        } else if (key === 'instagram') {
            const h = stripAt(s);
            s = h ? `https://www.instagram.com/${encodeURIComponent(h)}/` : '';
        } else if (key === 'facebook') {
            const h = stripAt(s);
            s = h ? `https://www.facebook.com/${encodeURIComponent(h)}` : '';
        } else if (key === 'youtube') {
            const h = stripAt(s);
            s = h ? `https://www.youtube.com/@${encodeURIComponent(h)}` : '';
        } else if (key === 'discord') {
            const invite = stripAt(s).replace(/^discord\.gg\//i, '');
            s = invite ? `https://discord.gg/${encodeURIComponent(invite)}` : '';
        } else {
            s = `https://${s.replace(/^\/+/, '')}`;
        }
    }

    if (/^\/\//.test(s)) {
        s = `https:${s}`;
    }

    try {
        const u = new URL(s);
        if (u.protocol !== 'http:' && u.protocol !== 'https:') {
            return null;
        }

        return u.href;
    } catch {
        return null;
    }
}
