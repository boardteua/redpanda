import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Echo для Laravel Reverb (протокол Pusher).
 * Повертає null, якщо немає VITE_REVERB_APP_KEY (наприклад, production build без WS).
 *
 * @param {string|null|undefined} bearerToken — опційно: Auth0 access token для POST /broadcasting/auth (T76).
 */
export function createEcho(bearerToken = null) {
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) {
        return null;
    }

    window.Pusher = Pusher;

    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const rawPort = import.meta.env.VITE_REVERB_PORT;
    const parsed =
        rawPort !== undefined && rawPort !== null && String(rawPort).trim() !== ''
            ? Number(String(rawPort).trim())
            : NaN;
    const wsPort = Number.isFinite(parsed) && parsed > 0 ? parsed : scheme === 'https' ? 443 : 80;

    /** @type {Record<string, unknown>} */
    const opts = {
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
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
