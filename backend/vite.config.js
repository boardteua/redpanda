import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue2';
import tailwindcss from '@tailwindcss/vite';

/**
 * PHP dotenv розгортає ${REVERB_PORT} у .env; Vite loadEnv — ні. Тоді в клієнт потрапляє
 * буквальний рядок або порожнє значення, Echo падає на порт сторінки (напр. 8080), а не Reverb.
 */
function resolveReverbClientPort(env) {
    const vite = env.VITE_REVERB_PORT;
    if (vite && !String(vite).startsWith('${')) {
        return String(vite).trim();
    }
    if (env.REVERB_PORT) {
        return String(env.REVERB_PORT).trim();
    }
    return '';
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const reverbClientPort = resolveReverbClientPort(env);

    return {
        define:
            reverbClientPort !== ''
                ? {
                      'import.meta.env.VITE_REVERB_PORT': JSON.stringify(reverbClientPort),
                  }
                : {},
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
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
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
