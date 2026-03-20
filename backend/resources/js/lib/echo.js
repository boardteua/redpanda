import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

/**
 * Echo для Laravel Reverb (протокол Pusher).
 * Повертає null, якщо немає VITE_REVERB_APP_KEY (наприклад, production build без WS).
 */
export function createEcho() {
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    if (!key) {
        return null;
    }

    window.Pusher = Pusher;

    const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';
    const port = import.meta.env.VITE_REVERB_PORT;

    return new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: import.meta.env.VITE_REVERB_HOST ?? 'localhost',
        wsPort: port ? Number(port) : 80,
        wssPort: port ? Number(port) : 443,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authEndpoint: `${window.location.origin}/broadcasting/auth`,
        auth: {
            withCredentials: true,
        },
    });
}
