<template>
    <div class="rp-auth-landing flex min-h-0 flex-1 flex-col px-4 py-10 pb-12 sm:px-6">
        <div class="rp-auth-landing-theme">
            <RpButton
                variant="ghost"
                class="shrink-0 text-sm"
                aria-label="Перемкнути тему оформлення"
                @click="cycleTheme"
            >
                {{ themeLabel }}
            </RpButton>
        </div>

        <main id="main-content" class="mx-auto w-full max-w-md flex-1" tabindex="-1">
            <div class="rp-auth-landing-card p-6">
                <h1 class="text-xl font-semibold text-[var(--rp-text)]">Встановлення пароля</h1>
                <p class="mt-2 text-sm text-[var(--rp-text-muted)]">
                    Обліковий запис перенесено зі старого чату. Щоб користуватися входом за паролем, встановіть новий пароль
                    через лист на вашу збережену адресу.
                </p>

                <div v-if="formError" class="rp-banner mt-4" role="alert" aria-live="polite">
                    {{ formError }}
                </div>
                <div
                    v-if="successMessage"
                    class="rp-banner mt-4 border-green-200 bg-green-50 text-green-900 dark:border-green-900 dark:bg-green-950 dark:text-green-100"
                    role="status"
                >
                    {{ successMessage }}
                </div>

                <div class="mt-6 space-y-4">
                    <p v-if="maskedEmail" class="text-sm text-[var(--rp-text-muted)]">
                        Лист буде надіслано на:
                        <span class="font-medium text-[var(--rp-text)]">{{ maskedEmail }}</span>
                    </p>
                    <RpButton class="w-full" :loading="loading" :disabled="loading || Boolean(successMessage)" @click="sendLink">
                        Надіслати лист на мою пошту
                    </RpButton>
                    <p class="text-center text-sm">
                        <router-link to="/forgot-password" class="rp-focusable text-[var(--rp-link)] hover:underline">
                            Відкрити сторінку «Забули пароль»
                        </router-link>
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
import RpButton from '../components/ui/RpButton.vue';

import { getResolvedTheme, THEME_KEY } from '../chat/chatRoomConstants';

export default {
    name: 'LegacyPasswordSetup',
    components: { RpButton },
    data() {
        return {
            themeUi: 'system',
            loading: false,
            formError: '',
            successMessage: '',
            maskedEmail: '',
            requiresSetup: false,
        };
    },
    computed: {
        themeLabel() {
            if (this.themeUi === 'light') {
                return 'Тема: світла';
            }
            if (this.themeUi === 'dark') {
                return 'Тема: темна';
            }

            return 'Тема: як у системі';
        },
    },
    created() {
        this.themeUi = getResolvedTheme();
    },
    async mounted() {
        document.documentElement.setAttribute('data-theme', this.themeUi);
        await this.ensureSanctum();
        await this.loadUser();
        if (!this.requiresSetup) {
            await this.$router.replace({ name: 'chat' }).catch(() => {});
        }
    },
    methods: {
        cycleTheme() {
            const order = ['system', 'light', 'dark'];
            const i = Math.max(0, order.indexOf(this.themeUi));
            const next = order[(i + 1) % order.length];
            this.themeUi = next;
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem(THEME_KEY, next);
        },
        async ensureSanctum() {
            await window.axios.get('/sanctum/csrf-cookie');
        },
        maskEmail(email) {
            if (!email || typeof email !== 'string') {
                return '';
            }
            const at = email.indexOf('@');
            if (at <= 1) {
                return email;
            }
            const local = email.slice(0, at);
            const domain = email.slice(at);
            const vis = local.slice(0, 2);
            return `${vis}…${domain}`;
        },
        async loadUser() {
            try {
                const { data } = await window.axios.get('/api/v1/auth/user');
                const u = data.data;
                if (!u || u.guest) {
                    await this.$router.replace({ path: '/' }).catch(() => {});

                    return;
                }
                this.requiresSetup = Boolean(u.requires_password_setup);
                this.maskedEmail = this.maskEmail(u.email || '');
            } catch {
                await this.$router.replace({ path: '/' }).catch(() => {});
            }
        },
        async sendLink() {
            this.formError = '';
            this.loading = true;
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.post('/api/v1/auth/account-legacy-password-link');
                this.successMessage =
                    (data && data.message) || 'Перевірте поштову скриньку: має надійти лист із посиланням.';
            } catch (e) {
                const status = e.response?.status;
                const msg = e.response?.data?.message;
                if (status === 422 && typeof msg === 'string') {
                    this.formError = msg;
                } else if (status === 429) {
                    this.formError = 'Забагато спроб. Зачекайте хвилину й спробуйте знову.';
                } else {
                    this.formError = 'Не вдалося надіслати лист. Спробуйте пізніше.';
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
