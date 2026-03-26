/** Підписи полів соцмереж у модалі «Інформація про користувача». */
export const USER_INFO_SOCIAL_LABELS = {
    facebook: 'Facebook',
    instagram: 'Instagram',
    telegram: 'Telegram',
    twitter: 'X / Twitter',
    youtube: 'YouTube',
    tiktok: 'TikTok',
    discord: 'Discord',
    website: 'Сайт',
};

/**
 * @param {Record<string, unknown>|null|undefined} socialLinks
 * @returns {{ key: string, label: string, href: string }[]}
 */
export function buildUserInfoSocialLinks(socialLinks) {
    if (!socialLinks || typeof socialLinks !== 'object') {
        return [];
    }
    const out = [];
    Object.keys(USER_INFO_SOCIAL_LABELS).forEach((key) => {
        const raw = socialLinks[key];
        if (raw == null || !String(raw).trim()) {
            return;
        }
        const href = normalizeSocialHref(key, raw);
        if (!href) {
            return;
        }
        out.push({ key, label: USER_INFO_SOCIAL_LABELS[key], href });
    });

    return out;
}

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
