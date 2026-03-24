import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Echo для Laravel Reverb (протокол Pusher).
 * Повертає null, якщо немає VITE_REVERB_APP_KEY (наприклад, production build без WS).
 *
 * Прод за nginx: публічний хост/443 у бандл підставляє vite.config.js з APP_URL (див. docker/nginx/host-nginx-reverb-proxy.example.conf).
 *
 * @param {string|null|undefined} bearerToken — опційно: Auth0 access token для POST /broadcasting/auth (T76).
 */
export function createEcho(bearerToken = null) {
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) {
        return null;
    }

    window.Pusher = Pusher;

    const schemeRaw = import.meta.env.VITE_REVERB_SCHEME;
    const scheme =
        schemeRaw !== undefined && schemeRaw !== null && String(schemeRaw).trim() !== ''
            ? String(schemeRaw).trim()
            : 'https';
    const rawPort = import.meta.env.VITE_REVERB_PORT;
    const parsed =
        rawPort !== undefined && rawPort !== null && String(rawPort).trim() !== ''
            ? Number(String(rawPort).trim())
            : NaN;
    const wsPort = Number.isFinite(parsed) && parsed > 0 ? parsed : scheme === 'https' ? 443 : 80;

    const hostRaw = import.meta.env.VITE_REVERB_HOST;
    const wsHost =
        hostRaw !== undefined && hostRaw !== null && String(hostRaw).trim() !== ''
            ? String(hostRaw).trim()
            : 'localhost';

    /** @type {Record<string, unknown>} */
    const opts = {
        broadcaster: 'reverb',
        key,
        wsHost,
        wsPort,
        wssPort: wsPort,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${window.location.origin}/broadcasting/auth`,
        auth: {
            headers: {},
            withCredentials: true,
        },
    };
    if (bearerToken) {
        opts.bearerToken = bearerToken;
    }

    return new Echo(opts);
}
