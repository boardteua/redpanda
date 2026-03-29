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
const STORAGE_KEY = 'rp_web_push_prompt_dismissed_session';

function base64UrlToUint8Array(value) {
    const padding = '='.repeat((4 - (value.length % 4 || 4)) % 4);
    const normalized = (value + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(normalized);

    return Uint8Array.from(raw, (ch) => ch.charCodeAt(0));
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
            return this.canShow && !this.dismissed && this.permission !== 'denied' && (!this.subscribed || this.error !== '');
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
                this.subscribed = false;
                return;
            }

            try {
                const registration = await navigator.serviceWorker.ready;
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
                    await this.persistSubscription(existing);
                } catch {
                    /* Локальна підписка вже є; не лишаємо плашку через тимчасову помилку повторної синхронізації. */
                }
                this.subscribed = true;
            } catch {
                this.subscribed = false;
            }
        },
        async onPrimaryAction() {
            this.busy = true;
            this.error = '';
            try {
                let permission = this.permission;
                if (permission === 'default') {
                    permission = await Notification.requestPermission();
                    this.permission = permission;
                }
                if (permission !== 'granted') {
                    return;
                }

                const registration = await navigator.serviceWorker.ready;
                const existing = await registration.pushManager.getSubscription();
                const subscription =
                    existing
                    || (await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: base64UrlToUint8Array(window.__RP_WEB_PUSH__.vapidPublicKey),
                    }));

                await this.persistSubscription(subscription);
                this.subscribed = true;
                this.dismissed = false;
            } catch {
                this.error = 'Браузер не зміг увімкнути push для цього пристрою.';
            } finally {
                this.busy = false;
            }
        },
        async persistSubscription(subscription) {
            const payload = subscription.toJSON();
            if (Array.isArray(window.PushManager?.supportedContentEncodings)) {
                payload.contentEncoding = window.PushManager.supportedContentEncodings[0] || null;
            }

            await this.ensureSanctum();
            await window.axios.post('/api/v1/push/subscriptions', {
                subscription: payload,
            });
        },
        async removeSubscription(subscription, unsubscribe = true) {
            await this.ensureSanctum();
            await window.axios.delete('/api/v1/push/subscriptions', {
                data: {
                    endpoint: subscription.endpoint,
                },
            });
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
