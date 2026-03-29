<template>
    <div
        v-if="showBar"
        class="rp-web-push"
        role="region"
        aria-label="Налаштування push-сповіщень"
        aria-describedby="rp-web-push-desc"
    >
        <p id="rp-web-push-desc" class="rp-web-push__text">
            {{ promptText }}
        </p>
        <p v-if="error" class="rp-web-push__error">
            {{ error }}
        </p>
        <div class="rp-web-push__actions">
            <RpButton variant="primary" native-type="button" :disabled="busy" @click="onPrimaryAction">
                {{ primaryLabel }}
            </RpButton>
            <RpButton variant="ghost" native-type="button" :disabled="busy" @click="dismiss">
                Пізніше
            </RpButton>
        </div>
    </div>
</template>

<script>
import {
    RP_PUSH_SW_PATH,
    SW_SCOPE_COVERAGE_USER_ERROR,
    workerScriptIsOurPushSw,
} from '../utils/rpWebPushSw.js';

const STORAGE_KEY = 'rp_web_push_prompt_dismissed_session';

/** Обриває завислі Promise (subscribe / мережа без timeout). */
function withTimeout(promise, ms) {
    let timeoutId;
    const timeoutPromise = new Promise((_, reject) => {
        timeoutId = window.setTimeout(() => reject(new Error('rp_timeout')), ms);
    });
    return Promise.race([promise, timeoutPromise]).finally(() => {
        if (timeoutId != null) {
            window.clearTimeout(timeoutId);
        }
    });
}

/** Scope має бути `/`, інакше /chat/* поза registration.scope (див. vite-plugin-pwa scope та nginx Service-Worker-Allowed). */
function registrationScopeIsSiteRoot(registration) {
    if (!registration || typeof registration.scope !== 'string' || registration.scope === '') {
        return false;
    }
    const pathname = new URL(registration.scope, window.location.origin).pathname;

    return pathname === '/' || pathname === '';
}

/**
 * Не покладатися лише на navigator.serviceWorker.ready: при першому встановленні
 * Workbox precache install може тривати >45s, а без активного SW ready не виконується вчасно.
 */
async function obtainRegistrationForPush(maxMs) {
    let reg = await navigator.serviceWorker.getRegistration();
    const matchesOurSw = (r) =>
        workerScriptIsOurPushSw(r.active && r.active.scriptURL)
        || workerScriptIsOurPushSw(r.waiting && r.waiting.scriptURL)
        || workerScriptIsOurPushSw(r.installing && r.installing.scriptURL);
    if (!reg || !matchesOurSw(reg)) {
        const regs = await navigator.serviceWorker.getRegistrations();
        const ours = regs.find((r) => matchesOurSw(r));
        if (ours) {
            reg = ours;
        }
    }
    if (reg && !registrationScopeIsSiteRoot(reg)) {
        try {
            await reg.unregister();
        } catch {
            /* ignore */
        }
        reg = null;
    }
    if (!reg) {
        reg = await navigator.serviceWorker.register(RP_PUSH_SW_PATH, {
            scope: '/',
            updateViaCache: 'none',
        });
    }
    await waitForRegistrationActivated(reg, maxMs);

    return reg;
}

function waitForRegistrationActivated(registration, maxMs) {
    if (registration.active) {
        return Promise.resolve();
    }
    const worker = registration.installing || registration.waiting;
    if (worker) {
        return new Promise((resolve, reject) => {
            const t = window.setTimeout(() => reject(new Error('rp_timeout')), maxMs);
            const done = () => {
                window.clearTimeout(t);
                resolve();
            };
            const onState = () => {
                if (worker.state === 'redundant') {
                    worker.removeEventListener('statechange', onState);
                    window.clearTimeout(t);
                    reject(new Error('sw_redundant'));
                    return;
                }
                if (registration.active || worker.state === 'activated') {
                    worker.removeEventListener('statechange', onState);
                    done();
                }
            };
            worker.addEventListener('statechange', onState);
            if (registration.active || worker.state === 'activated') {
                worker.removeEventListener('statechange', onState);
                done();
            }
        });
    }

    return withTimeout(navigator.serviceWorker.ready, maxMs).then(() => {});
}

function base64UrlToUint8Array(value) {
    const padding = '='.repeat((4 - (value.length % 4 || 4)) % 4);
    const normalized = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(normalized);

    return Uint8Array.from(raw, (ch) => ch.charCodeAt(0));
}

/** Некоректний ключ дає зламаний subscribe() / зависання в окремих браузерах. */
function vapidPublicKeyLooksValid(base64UrlKey) {
    if (base64UrlKey == null || typeof base64UrlKey !== 'string') {
        return false;
    }
    const trimmed = base64UrlKey.trim();
    if (trimmed === '') {
        return false;
    }
    try {
        const bytes = base64UrlToUint8Array(trimmed);

        return bytes.length === 65 && bytes[0] === 4;
    } catch {
        return false;
    }
}

/**
 * Якщо SW зареєстрований під /build/sw.js без Service-Worker-Allowed, scope = /build/ — сторінки /chat/* поза ним.
 */
function serviceWorkerScopeCoversCurrentPage(registration) {
    if (!registration || typeof registration.scope !== 'string' || registration.scope === '') {
        return false;
    }
    let scopePath = new URL(registration.scope, window.location.origin).pathname;
    if (!scopePath.endsWith('/')) {
        scopePath += '/';
    }
    let pagePath = window.location.pathname;
    if (!pagePath.endsWith('/')) {
        pagePath += '/';
    }

    return pagePath.startsWith(scopePath);
}

export default {
    name: 'RpWebPushPrompt',
    props: {
        user: {
            type: Object,
            default: null,
        },
        ensureSanctum: {
            type: Function,
            required: true,
        },
    },
    data() {
        return {
            busy: false,
            dismissed: false,
            error: '',
            permission: 'default',
            subscribed: false,
            /** T169: для permission === granted не показувати плашку, доки не завершена перевірка pushManager (уникнути миготіння після F5). */
            subscriptionSyncDone: false,
        };
    },
    computed: {
        configured() {
            const cfg = window.__RP_WEB_PUSH__ || null;
            const key = cfg && typeof cfg.vapidPublicKey === 'string' ? cfg.vapidPublicKey.trim() : '';

            return key !== '';
        },
        supported() {
            return (
                import.meta.env.PROD &&
                typeof window !== 'undefined' &&
                'Notification' in window &&
                'serviceWorker' in navigator &&
                'PushManager' in window
            );
        },
        canShow() {
            return this.supported && this.configured && this.user && !this.user.guest;
        },
        showBar() {
            if (!this.canShow || this.dismissed || this.permission === 'denied') {
                return false;
            }
            const needSubscriptionCheck = this.permission === 'granted';
            if (needSubscriptionCheck && !this.subscriptionSyncDone) {
                return false;
            }

            return !this.subscribed || this.error !== '';
        },
        primaryLabel() {
            if (this.busy) {
                return 'Зберігаємо…';
            }

            return this.permission === 'default' ? 'Увімкнути push' : 'Підключити push';
        },
        promptText() {
            if (this.error) {
                return 'Не вдалося підключити push-сповіщення. Спробуйте ще раз.';
            }
            if (this.permission === 'default') {
                return 'Дозвольте push-сповіщення, щоб отримувати нові повідомлення навіть без відкритої вкладки.';
            }

            return 'Push-сповіщення ще не підключено для цього браузера.';
        },
    },
    watch: {
        user: {
            immediate: true,
            handler() {
                void this.refreshState();
            },
        },
    },
    created() {
        if (
            import.meta.env.PROD
            && typeof window !== 'undefined'
            && 'Notification' in window
            && 'serviceWorker' in navigator
            && 'PushManager' in window
        ) {
            this.permission = Notification.permission;
        }
    },
    mounted() {
        try {
            this.dismissed = sessionStorage.getItem(STORAGE_KEY) === '1';
        } catch {
            this.dismissed = false;
        }
    },
    methods: {
        dismiss() {
            this.dismissed = true;
            try {
                sessionStorage.setItem(STORAGE_KEY, '1');
            } catch {
                /* ignore */
            }
        },
        async refreshState() {
            this.error = '';
            this.permission = this.supported ? Notification.permission : 'unsupported';

            if (!this.canShow) {
                if (this.user) {
                    this.subscribed = false;
                }
                this.subscriptionSyncDone = true;
                return;
            }

            this.subscriptionSyncDone = this.permission !== 'granted';

            try {
                const registration = await obtainRegistrationForPush(120000);
                const covers = serviceWorkerScopeCoversCurrentPage(registration);
                if (!covers) {
                    this.subscribed = false;
                    this.error = SW_SCOPE_COVERAGE_USER_ERROR;
                    return;
                }
                const existing = await registration.pushManager.getSubscription();
                if (!existing) {
                    this.subscribed = false;
                    return;
                }

                if (this.permission !== 'granted') {
                    await this.removeSubscription(existing, false);
                    return;
                }

                try {
                    await withTimeout(this.persistSubscription(existing), 60000);
                } catch {
                    /* Локальна підписка вже є; не лишаємо плашку через тимчасову помилку повторної синхронізації. */
                }
                this.subscribed = true;
            } catch {
                this.subscribed = false;
            } finally {
                this.subscriptionSyncDone = true;
            }
        },
        async onPrimaryAction() {
            this.busy = true;
            this.error = '';
            try {
                let permission = this.permission;
                if (permission === 'default') {
                    permission = await withTimeout(Notification.requestPermission(), 120000);
                    this.permission = permission;
                }
                if (permission !== 'granted') {
                    return;
                }

                const vapidKey = window.__RP_WEB_PUSH__ && window.__RP_WEB_PUSH__.vapidPublicKey;
                const vapidOk = vapidPublicKeyLooksValid(vapidKey);
                if (!vapidOk) {
                    this.error =
                        'Ключ VAPID на сервері некоректний (очікується публічний ключ 65 байт). Перевірте WEB_PUSH_VAPID_PUBLIC_KEY у .env.';
                    return;
                }

                const registration = await obtainRegistrationForPush(120000);
                if (!serviceWorkerScopeCoversCurrentPage(registration)) {
                    this.error = SW_SCOPE_COVERAGE_USER_ERROR;
                    return;
                }

                const existing = await registration.pushManager.getSubscription();
                const subscription =
                    existing
                    || (await withTimeout(
                        registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: base64UrlToUint8Array(window.__RP_WEB_PUSH__.vapidPublicKey),
                        }),
                        90000,
                    ));

                await withTimeout(this.persistSubscription(subscription), 60000);
                this.subscribed = true;
                this.dismissed = false;
            } catch (e) {
                const timedOut =
                    (e && String(e.message || '') === 'rp_timeout')
                    || (e && e.code === 'ECONNABORTED')
                    || (e && /timeout/i.test(String(e.message || '')));
                try {
                    const reg = await obtainRegistrationForPush(30000);
                    const sub = await reg.pushManager.getSubscription();
                    if (sub && Notification.permission === 'granted') {
                        this.subscribed = true;
                        this.error = '';
                        return;
                    }
                } catch {
                    /* ignore */
                }
                this.error = timedOut
                    ? 'Час очікування вичерпано (мережа або браузер). Спробуйте ще раз.'
                    : 'Браузер не зміг увімкнути push для цього пристрою.';
            } finally {
                this.busy = false;
            }
        },
        async persistSubscription(subscription) {
            const payload = subscription.toJSON();
            if (Array.isArray(window.PushManager?.supportedContentEncodings)) {
                payload.contentEncoding = window.PushManager.supportedContentEncodings[0] || null;
            }

            await withTimeout(this.ensureSanctum(), 20000);
            await window.axios.post(
                '/api/v1/push/subscriptions',
                {
                    subscription: payload,
                },
                { timeout: 45000 },
            );
        },
        async removeSubscription(subscription, unsubscribe = true) {
            await withTimeout(this.ensureSanctum(), 20000);
            await window.axios.delete(
                '/api/v1/push/subscriptions',
                {
                    data: {
                        endpoint: subscription.endpoint,
                    },
                    timeout: 45000,
                },
            );
            if (unsubscribe && typeof subscription.unsubscribe === 'function') {
                await subscription.unsubscribe();
            }
            this.subscribed = false;
        },
    },
};
</script>

<style scoped>
.rp-web-push {
    position: fixed;
    z-index: 61;
    left: 1rem;
    right: 1rem;
    bottom: calc(1rem + env(safe-area-inset-bottom));
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border: 1px solid color-mix(in srgb, var(--rp-brand, #c2410c) 26%, var(--rp-border-subtle, #cbd5e1));
    border-radius: 1rem;
    background: color-mix(in srgb, var(--rp-surface, #fff) 95%, var(--rp-brand, #c2410c));
    box-shadow: 0 14px 40px rgb(15 23 42 / 0.16);
}

.rp-web-push__text,
.rp-web-push__error {
    margin: 0;
    font-size: 0.9375rem;
    line-height: 1.4;
}

.rp-web-push__text {
    color: var(--rp-text, #0f172a);
}

.rp-web-push__error {
    color: var(--rp-danger, #b91c1c);
}

.rp-web-push__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

@media (min-width: 768px) {
    .rp-web-push {
        right: auto;
        max-width: 32rem;
    }
}
</style>
