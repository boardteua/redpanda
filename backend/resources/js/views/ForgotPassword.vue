<template>
    <div class="rp-auth-landing flex flex-col px-4 py-10 pb-12 sm:px-6">
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
                <h1 class="text-xl font-semibold text-[var(--rp-text)]">Відновлення пароля</h1>
                <p class="mt-2 text-sm text-[var(--rp-text-muted)]">
                    Вкажіть email облікового запису з паролем. Якщо ви входите лише через Google чи інший сервіс — використайте той самий спосіб входу.
                </p>

                <div v-if="formError" class="rp-banner mt-4" role="alert" aria-live="polite">
                    {{ formError }}
                </div>
                <div v-if="successMessage" class="rp-banner mt-4 border-green-200 bg-green-50 text-green-900 dark:border-green-900 dark:bg-green-950 dark:text-green-100" role="status">
                    {{ successMessage }}
                </div>

                <form class="mt-6 space-y-4" novalidate @submit.prevent="submit">
                    <div>
                        <label class="rp-label" for="fp-email">Електронна пошта</label>
                        <input
                            id="fp-email"
                            v-model.trim="email"
                            class="rp-input rp-focusable"
                            type="email"
                            name="email"
                            autocomplete="email"
                            required
                            :disabled="loading || Boolean(successMessage)"
                            :aria-invalid="fieldInvalid('email')"
                        />
                        <p v-if="fieldError('email')" class="rp-error-text" role="alert">
                            {{ fieldError('email') }}
                        </p>
                    </div>
                    <RpButton native-type="submit" class="w-full" :loading="loading" :disabled="loading || Boolean(successMessage)">
                        Надіслати посилання
                    </RpButton>
                </form>
            </div>
        </main>
    </div>
</template>

<script>
import RpButton from '../components/ui/RpButton.vue';

const THEME_KEY = 'redpanda-theme';

export default {
    name: 'ForgotPassword',
    components: { RpButton },
    data() {
        return {
            themeUi: 'system',
            email: '',
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
    },
    created() {
        this.themeUi = localStorage.getItem(THEME_KEY) || 'system';
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
            if (this.successMessage) {
                return;
            }
            this.clearErrors();
            this.loading = true;
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.post('/api/v1/auth/forgot-password', {
                    email: this.email,
                });
                this.successMessage =
                    (data && typeof data.message === 'string' && data.message.trim()) ||
                    'Якщо для цієї адреси є обліковий запис з паролем, ми надіслали лист із посиланням для скидання.';
            } catch (e) {
                const status = e.response?.status;
                if (status === 422) {
                    this.fieldErrors = e.response.data.errors || {};
                    this.formError = e.response.data.message || 'Перевірте поле email.';
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
