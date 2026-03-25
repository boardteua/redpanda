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
                <p class="mb-4">
                    <router-link to="/" class="rp-focusable text-sm font-medium text-[var(--rp-link)] hover:underline">
                        ← Назад на головну
                    </router-link>
                </p>
                <h1 class="text-xl font-semibold text-[var(--rp-text)]">Новий пароль</h1>
                <p v-if="!tokenFromQuery || !emailFromQuery" class="mt-2 text-sm text-[var(--rp-error)]" role="alert">
                    Некоректне посилання. Запросіть нове скидання пароля з вітальні.
                </p>
                <p v-else class="mt-2 text-sm text-[var(--rp-text-muted)]">
                    Обліковий запис: <span class="font-medium text-[var(--rp-text)]">{{ emailFromQuery }}</span>
                </p>

                <div v-if="formError" class="rp-banner mt-4" role="alert" aria-live="polite">
                    {{ formError }}
                </div>
                <div v-if="successMessage" class="rp-banner mt-4" role="status">
                    {{ successMessage }}
                    <p class="mt-3">
                        <router-link to="/" class="rp-focusable font-medium text-[var(--rp-link)] underline underline-offset-2">
                            Перейти до входу
                        </router-link>
                    </p>
                </div>

                <form
                    v-if="tokenFromQuery && emailFromQuery && !successMessage"
                    class="mt-6 space-y-4"
                    novalidate
                    @submit.prevent="submit"
                >
                    <div>
                        <label class="rp-label" for="rp-pass">Новий пароль</label>
                        <input
                            id="rp-pass"
                            v-model="password"
                            class="rp-input rp-focusable"
                            type="password"
                            name="password"
                            autocomplete="new-password"
                            required
                            :disabled="loading || Boolean(successMessage)"
                            :aria-invalid="fieldInvalid('password')"
                        />
                        <p v-if="fieldError('password')" class="rp-error-text" role="alert">
                            {{ fieldError('password') }}
                        </p>
                    </div>
                    <div>
                        <label class="rp-label" for="rp-pass2">Підтвердження пароля</label>
                        <input
                            id="rp-pass2"
                            v-model="passwordConfirmation"
                            class="rp-input rp-focusable"
                            type="password"
                            name="password_confirmation"
                            autocomplete="new-password"
                            required
                            :disabled="loading || Boolean(successMessage)"
                            :aria-invalid="fieldInvalid('password_confirmation')"
                        />
                        <p v-if="fieldError('password_confirmation')" class="rp-error-text" role="alert">
                            {{ fieldError('password_confirmation') }}
                        </p>
                    </div>
                    <p v-if="fieldError('token')" class="rp-error-text" role="alert">
                        {{ fieldError('token') }}
                    </p>
                    <RpButton native-type="submit" class="w-full" :loading="loading" :disabled="loading || Boolean(successMessage)">
                        Зберегти пароль
                    </RpButton>
                </form>
            </div>
        </main>
    </div>
</template>

<script>
import RpButton from '../components/ui/RpButton.vue';
import { getResolvedTheme, THEME_KEY } from '../chat/chatRoomConstants';

export default {
    name: 'ResetPassword',
    components: { RpButton },
    data() {
        return {
            themeUi: 'system',
            password: '',
            passwordConfirmation: '',
            loading: false,
            formError: '',
            fieldErrors: {},
            successMessage: '',
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
        tokenFromQuery() {
            const t = this.$route.query.token;

            return t != null && String(t).trim() !== '' ? String(t).trim() : '';
        },
        emailFromQuery() {
            const e = this.$route.query.email;

            return e != null && String(e).trim() !== '' ? String(e).trim() : '';
        },
    },
    created() {
        this.themeUi = getResolvedTheme();
    },
    mounted() {
        document.documentElement.setAttribute('data-theme', this.themeUi);
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
        fieldInvalid(field) {
            return this.fieldErrors[field] ? 'true' : 'false';
        },
        fieldError(field) {
            const e = this.fieldErrors[field];

            return Array.isArray(e) ? e[0] : e || '';
        },
        clearErrors() {
            this.formError = '';
            this.fieldErrors = {};
        },
        async ensureSanctum() {
            await window.axios.get('/sanctum/csrf-cookie');
        },
        async submit() {
            if (!this.tokenFromQuery || !this.emailFromQuery || this.successMessage) {
                return;
            }
            this.clearErrors();
            this.loading = true;
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.post('/api/v1/auth/reset-password', {
                    token: this.tokenFromQuery,
                    email: this.emailFromQuery,
                    password: this.password,
                    password_confirmation: this.passwordConfirmation,
                });
                this.successMessage =
                    (data && typeof data.message === 'string' && data.message.trim()) ||
                    'Пароль оновлено. Увійдіть з новим паролем.';
            } catch (e) {
                const status = e.response?.status;
                if (status === 422) {
                    this.fieldErrors = e.response.data.errors || {};
                    this.formError = e.response.data.message || 'Перевірте поля форми.';
                } else if (status === 429) {
                    this.formError = 'Забагато спроб. Зачекайте хвилину й спробуйте знову.';
                } else {
                    this.formError = 'Сталася помилка. Спробуйте пізніше.';
                }
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
