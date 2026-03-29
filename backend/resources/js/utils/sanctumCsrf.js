/**
 * T162: умовний виклик GET /sanctum/csrf-cookie — зайвий round-trip на Slow 3G,
 * якщо XSRF-TOKEN уже в cookie (axios надсилає X-XSRF-TOKEN через withXSRFToken).
 * При 419 див. bootstrap.js (одноразовий retry після оновлення cookie).
 */
export function hasXsrfTokenCookie() {
    if (typeof document === 'undefined') {
        return false;
    }
    return document.cookie.split(';').some((part) => {
        const t = part.trim();
        return t.startsWith('XSRF-TOKEN=') && t.length > 'XSRF-TOKEN='.length;
    });
}
