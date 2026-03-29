/** Шлях до зібраного SW (vite-plugin-pwa injectManifest, `buildBase: '/build/'`). */
export const RP_PUSH_SW_PATH = '/build/sw.js';

/** Повідомлення, коли registration.scope не покриває поточний шлях (типово вузький scope /build/). */
export const SW_SCOPE_COVERAGE_USER_ERROR =
    'Service worker не охоплює цю сторінку (зазвичай бракує заголовка Service-Worker-Allowed для /build/sw.js у nginx). Оновіть конфіг і перезапустіть nginx, потім жорстке оновлення сторінки.';

/**
 * Чи вказує worker на наш push SW, а не випадковий скрипт з підрядком "sw.js".
 *
 * @param {string} [scriptUrl]
 * @param {string} [pageOrigin] — для тестів; у браузері за замовчуванням `window.location.origin`
 */
export function workerScriptIsOurPushSw(scriptUrl, pageOrigin) {
    if (scriptUrl == null || typeof scriptUrl !== 'string' || scriptUrl === '') {
        return false;
    }
    const origin =
        pageOrigin
        ?? (typeof window !== 'undefined' && window.location ? window.location.origin : '');
    if (!origin) {
        return false;
    }
    try {
        const pathname = new URL(scriptUrl, origin).pathname;

        return pathname === RP_PUSH_SW_PATH;
    } catch {
        return false;
    }
}
