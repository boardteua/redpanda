import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';

/** Рядок на кшталт "${REVERB_HOST}" з .env без розгортання — не використовувати в клієнті. */
function isUnresolvedRef(value) {
    const s = value == null ? '' : String(value).trim();
    return s.startsWith('${');
}

function parseAppUrl(env) {
    const raw = env.APP_URL;
    if (!raw || isUnresolvedRef(raw)) {
        return null;
    }
    try {
        return new URL(String(raw).trim());
    } catch {
        return null;
    }
}

/**
 * PHP dotenv розгортає ${REVERB_*}; Vite loadEnv — ні. Тоді в бандл потрапляє буквальний "${…}" або порожньо.
 * Підставляємо з APP_URL (публічний хост після nginx), інакше з REVERB_* для локальної розробки.
 */
function resolveReverbClientHost(env) {
    const vite = env.VITE_REVERB_HOST;
    if (vite && !isUnresolvedRef(vite)) {
        return String(vite).trim();
    }
    const app = parseAppUrl(env);
    if (app?.hostname) {
        return app.hostname;
    }
    const rh = env.REVERB_HOST;
    if (rh && !isUnresolvedRef(rh)) {
        return String(rh).trim();
    }
    return '';
}

function resolveReverbClientScheme(env) {
    const vite = env.VITE_REVERB_SCHEME;
    if (vite && !isUnresolvedRef(vite)) {
        return String(vite).trim();
    }
    const app = parseAppUrl(env);
    if (app?.protocol === 'https:') {
        return 'https';
    }
    if (app?.protocol === 'http:') {
        return 'http';
    }
    const rs = env.REVERB_SCHEME;
    if (rs && !isUnresolvedRef(rs)) {
        return String(rs).trim();
    }
    return '';
}

/** Ключ додатку в URL `/app/{key}`; має збігатися з REVERB_APP_KEY у Reverb. */
function resolveReverbAppKey(env) {
    const vite = env.VITE_REVERB_APP_KEY;
    if (vite && !isUnresolvedRef(vite) && String(vite).trim() !== '') {
        return String(vite).trim();
    }
    const rk = env.REVERB_APP_KEY;
    if (rk && !isUnresolvedRef(rk) && String(rk).trim() !== '') {
        return String(rk).trim();
    }
    return '';
}

function resolveReverbClientPort(env) {
    const vite = env.VITE_REVERB_PORT;
    if (vite && !isUnresolvedRef(vite)) {
        return String(vite).trim();
    }
    const app = parseAppUrl(env);
    // HTTPS у APP_URL → браузер завжди через nginx (443), не на внутрішній REVERB_PORT=6001.
    if (app?.protocol === 'https:') {
        return app.port || '443';
    }
    if (app?.protocol === 'http:' && app.port) {
        return app.port;
    }
    const rp = env.REVERB_PORT;
    if (rp && !isUnresolvedRef(rp)) {
        return String(rp).trim();
    }
    if (app?.protocol === 'http:') {
        return '80';
    }
    return '';
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const reverbClientHost = resolveReverbClientHost(env);
    const reverbClientScheme = resolveReverbClientScheme(env);
    const reverbClientPort = resolveReverbClientPort(env);
    const reverbAppKey = resolveReverbAppKey(env);

    /** @type {Record<string, string>} */
    const defineReverb = {};
    if (reverbAppKey !== '') {
        defineReverb['import.meta.env.VITE_REVERB_APP_KEY'] = JSON.stringify(reverbAppKey);
    }
    if (reverbClientHost !== '') {
        defineReverb['import.meta.env.VITE_REVERB_HOST'] = JSON.stringify(reverbClientHost);
    }
    if (reverbClientScheme !== '') {
        defineReverb['import.meta.env.VITE_REVERB_SCHEME'] = JSON.stringify(reverbClientScheme);
    }
    if (reverbClientPort !== '') {
        defineReverb['import.meta.env.VITE_REVERB_PORT'] = JSON.stringify(reverbClientPort);
    }

    return {
        define: defineReverb,
        plugins: [
            laravel({
                input: [
                    'resources/css/welcome.css',
                    'resources/css/chat.css',
                    'resources/js/app.js',
                ],
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
            tailwindcss(),
            /** T163: PWA — Workbox лише прекешує хешовані JS/CSS/woff2 з `public/build`; HTML лишається з Laravel (свіжий CSRF). */
            VitePWA({
                registerType: 'autoUpdate',
                injectRegister: false,
                buildBase: '/build/',
                includeAssets: ['pwa-icon-192.png', 'pwa-icon-512.png'],
                manifest: {
                    name: 'Чат Рудої Панди',
                    short_name: 'Руда Панда',
                    description: 'Тернопільський анонімний чат',
                    start_url: '/',
                    scope: '/',
                    display: 'standalone',
                    theme_color: '#c2410c',
                    background_color: '#f1f5f9',
                    lang: 'uk',
                    icons: [
                        {
                            src: '/pwa-icon-192.png',
                            sizes: '192x192',
                            type: 'image/png',
                            purpose: 'any',
                        },
                        {
                            src: '/pwa-icon-512.png',
                            sizes: '512x512',
                            type: 'image/png',
                            purpose: 'any',
                        },
                    ],
                },
                workbox: {
                    globPatterns: ['**/*.{js,css,woff2}'],
                    /** Без offline HTML: навігації не перехоплюються; Blade залишається завжди з мережі. */
                    navigateFallback: null,
                },
                devOptions: {
                    enabled: false,
                },
            }),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
