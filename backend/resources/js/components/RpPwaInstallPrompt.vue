<template>
    <div
        v-if="showBar"
        class="rp-pwa-install"
        role="region"
        aria-label="Запропонувати встановити застосунок"
        aria-describedby="rp-pwa-install-desc"
    >
        <p id="rp-pwa-install-desc" class="rp-pwa-install__text">
            Встановіть чат на головний екран — швидший доступ і зручніше на телефоні.
        </p>
        <div class="rp-pwa-install__actions">
            <RpButton variant="primary" native-type="button" :disabled="installing" @click="onInstall">
                {{ installing ? 'Відкриваємо…' : 'Встановити' }}
            </RpButton>
            <RpButton variant="ghost" native-type="button" @click="onDismiss">
                Пізніше
            </RpButton>
        </div>
    </div>
</template>

<script>
const STORAGE_KEY = 'rp_pwa_install_prompt_dismissed_session';

export default {
    name: 'RpPwaInstallPrompt',
    data() {
        return {
            deferredPrompt: null,
            installing: false,
            dismissed: false,
            installed: false,
        };
    },
    computed: {
        showBar() {
            return (
                import.meta.env.PROD &&
                this.deferredPrompt != null &&
                !this.dismissed &&
                !this.installed
            );
        },
    },
    mounted() {
        if (!import.meta.env.PROD) {
            return;
        }
        try {
            this.dismissed = sessionStorage.getItem(STORAGE_KEY) === '1';
        } catch {
            this.dismissed = false;
        }
        this._onBeforeInstall = (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
        };
        this._onAppInstalled = () => {
            this.installed = true;
            this.deferredPrompt = null;
        };
        window.addEventListener('beforeinstallprompt', this._onBeforeInstall);
        window.addEventListener('appinstalled', this._onAppInstalled);
    },
    beforeDestroy() {
        if (this._onBeforeInstall) {
            window.removeEventListener('beforeinstallprompt', this._onBeforeInstall);
        }
        if (this._onAppInstalled) {
            window.removeEventListener('appinstalled', this._onAppInstalled);
        }
    },
    methods: {
        onDismiss() {
            this.dismissed = true;
            try {
                sessionStorage.setItem(STORAGE_KEY, '1');
            } catch {
                /* ignore */
            }
            this.deferredPrompt = null;
        },
        async onInstall() {
            const ev = this.deferredPrompt;
            if (!ev || typeof ev.prompt !== 'function') {
                return;
            }
            this.installing = true;
            try {
                await ev.prompt();
            } finally {
                this.installing = false;
                this.deferredPrompt = null;
            }
        },
    },
};
</script>

<style scoped>
.rp-pwa-install {
    position: fixed;
    z-index: 60;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    padding-bottom: max(0.75rem, env(safe-area-inset-bottom));
    background: color-mix(in srgb, var(--rp-surface, #fff) 96%, var(--rp-text, #0f172a));
    border-top: 1px solid var(--rp-border-subtle, #cbd5e1);
    box-shadow: 0 -8px 24px rgb(15 23 42 / 0.08);
}

.rp-pwa-install__text {
    margin: 0;
    flex: 1 1 12rem;
    font-size: 0.9375rem;
    line-height: 1.4;
    color: var(--rp-text, #0f172a);
}

.rp-pwa-install__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
}
</style>
