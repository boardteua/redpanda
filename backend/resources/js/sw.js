import { clientsClaim } from 'workbox-core';
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching';

self.skipWaiting();
clientsClaim();
cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

self.addEventListener('push', (event) => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch {
        payload = {
            body: event.data ? event.data.text() : 'Нове повідомлення',
        };
    }

    const data = payload && typeof payload.data === 'object' ? payload.data : {};
    const title =
        payload && typeof payload.title === 'string' && payload.title.trim() !== ''
            ? payload.title
            : 'Чат Рудої Панди';
    const body =
        payload && typeof payload.body === 'string' && payload.body.trim() !== ''
            ? payload.body
            : 'Нове повідомлення';

    const defaultIcon = new URL('/pwa/icon-192.png', self.location.origin).href;
    const defaultBadge = new URL('/pwa/icon-96.png', self.location.origin).href;
    const rawAuthor =
        payload && typeof payload.author_avatar_url === 'string' ? payload.author_avatar_url.trim() : '';
    const authorAvatarUrl = rawAuthor !== '' ? rawAuthor : null;
    const icon =
        authorAvatarUrl ||
        (payload && typeof payload.icon === 'string' && payload.icon.trim() !== ''
            ? payload.icon.trim()
            : defaultIcon);
    const badge =
        payload && typeof payload.badge === 'string' && payload.badge.trim() !== ''
            ? payload.badge.trim()
            : defaultBadge;

    const notifOptions = {
        body,
        tag: typeof payload.tag === 'string' ? payload.tag : undefined,
        icon,
        badge,
        data: {
            url: typeof data.url === 'string' && data.url.trim() !== '' ? data.url : '/chat',
            ...data,
        },
    };
    if (authorAvatarUrl) {
        notifOptions.image = authorAvatarUrl;
    }

    event.waitUntil(self.registration.showNotification(title, notifOptions));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const data = event.notification?.data || {};
    const targetUrl = new URL(
        typeof data.url === 'string' && data.url.trim() !== '' ? data.url : '/chat',
        self.location.origin,
    ).href;

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({
            type: 'window',
            includeUncontrolled: true,
        });

        for (const client of windows) {
            try {
                const clientOrigin = new URL(client.url).origin;
                if (clientOrigin !== self.location.origin) {
                    continue;
                }
                if ('navigate' in client && typeof client.navigate === 'function') {
                    await client.navigate(targetUrl);
                }
                if ('focus' in client) {
                    await client.focus();
                }

                return;
            } catch {
                /* try next */
            }
        }

        if (self.clients.openWindow) {
            await self.clients.openWindow(targetUrl);
        }
    })());
});
