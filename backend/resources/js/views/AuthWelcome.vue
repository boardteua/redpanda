<template>
    <div class="flex min-h-screen flex-col px-4 py-8 sm:px-6">
        <header
            class="mx-auto mb-8 flex w-full max-w-xl items-center justify-between gap-4"
        >
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--rp-text)] sm:text-2xl">
                    {{ appName }}
                </h1>
                <p class="mt-1 text-sm text-[var(--rp-text-muted)]">
                    Український онлайн-чат
                </p>
            </div>
            <button
                type="button"
                class="rp-focusable rp-btn rp-btn-ghost shrink-0 text-sm"
                aria-label="Перемкнути тему оформлення"
                @click="cycleTheme"
            >
                {{ themeLabel }}
            </button>
        </header>

        <main
            id="main-content"
            class="mx-auto w-full max-w-xl flex-1"
            tabindex="-1"
        >
            <div
                v-if="user"
                class="rp-panel"
                role="region"
                aria-labelledby="signed-in-heading"
            >
                <h2 id="signed-in-heading" class="text-lg font-semibold text-[var(--rp-text)]">
                    Ви в чаті
                </h2>
                <p class="mt-3 text-[var(--rp-text-muted)]">
                    Нік:
                    <strong class="text-[var(--rp-text)]">{{ user.user_name }}</strong>
                    <span v-if="user.guest" class="ml-1 text-sm">(гість)</span>
                </p>
                <p
                    v-if="!user.guest && user.email"
                    class="mt-1 text-sm text-[var(--rp-text-muted)]"
                >
                    {{ user.email }}
                </p>
                <router-link
                    :to="{ name: 'chat' }"
                    class="rp-focusable rp-btn rp-btn-primary mt-4 block w-full text-center no-underline"
                >
                    Відкрити чат
                </router-link>
                <router-link
                    :to="{ name: 'archive' }"
                    class="rp-focusable rp-btn rp-btn-ghost mt-3 block w-full text-center no-underline"
                >
                    Архів чату
                </router-link>
                <button
                    type="button"
                    class="rp-focusable rp-btn rp-btn-ghost mt-3 w-full"
                    :disabled="loading"
                    @click="logout"
                >
                    Вийти
                </button>
            </div>

            <div
                v-else
                class="rp-panel"
                role="region"
                :aria-labelledby="mode === 'login' ? 'login-heading' : 'register-heading'"
            >
                <div
                    class="mb-6 flex gap-2"
                    role="tablist"
                    aria-label="Режим форми"
                >
                    <button
                        id="tab-login"
                        type="button"
                        role="tab"
                        class="rp-tab rp-focusable"
                        :aria-selected="mode === 'login'"
                        aria-controls="auth-panel"
                        @click="setMode('login')"
                    >
                        Вхід
                    </button>
                    <button
                        id="tab-register"
                        type="button"
                        role="tab"
                        class="rp-tab rp-focusable"
                        :aria-selected="mode === 'register'"
                        aria-controls="auth-panel"
                        @click="setMode('register')"
                    >
                        Реєстрація
                    </button>
                </div>

                <div
                    id="auth-panel"
                    role="tabpanel"
                    :aria-labelledby="mode === 'login' ? 'tab-login' : 'tab-register'"
                >
                    <h2
                        id="login-heading"
                        :class="['text-lg font-semibold', mode === 'login' ? '' : 'rp-sr-only']"
                    >
                        Вхід
                    </h2>
                    <h2
                        id="register-heading"
                        :class="['text-lg font-semibold', mode === 'register' ? '' : 'rp-sr-only']"
                    >
                        Реєстрація
                    </h2>

                    <div
                        v-if="formError"
                        class="rp-banner mt-4"
                        role="alert"
                        aria-live="polite"
                    >
                        {{ formError }}
                    </div>

                    <form
                        v-if="mode === 'login'"
                        class="mt-4 space-y-4"
                        novalidate
                        @submit.prevent="submitLogin"
                    >
                        <div>
                            <label class="rp-label" for="login-user_name">Ім’я користувача</label>
                            <input
                                id="login-user_name"
                                v-model="loginForm.user_name"
                                class="rp-input rp-focusable"
                                type="text"
                                name="user_name"
                                autocomplete="username"
                                required
                                :aria-invalid="fieldInvalid('user_name')"
                            />
                            <p v-if="fieldError('user_name')" class="rp-error-text" role="alert">
                                {{ fieldError('user_name') }}
                            </p>
                        </div>
                        <div>
                            <label class="rp-label" for="login-password">Пароль</label>
                            <input
                                id="login-password"
                                v-model="loginForm.password"
                                class="rp-input rp-focusable"
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                required
                                :aria-invalid="fieldInvalid('password')"
                            />
                            <p v-if="fieldError('password')" class="rp-error-text" role="alert">
                                {{ fieldError('password') }}
                            </p>
                        </div>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text-muted)]">
                            <input
                                v-model="loginForm.remember"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border-[var(--rp-border-subtle)]"
                            />
                            Запам’ятати мене
                        </label>
                        <button
                            type="submit"
                            class="rp-focusable rp-btn rp-btn-primary w-full"
                            :disabled="loading"
                        >
                            Увійти
                        </button>
                    </form>

                    <form
                        v-else
                        class="mt-4 space-y-4"
                        novalidate
                        @submit.prevent="submitRegister"
                    >
                        <div>
                            <label class="rp-label" for="reg-user_name">Ім’я користувача</label>
                            <input
                                id="reg-user_name"
                                v-model="registerForm.user_name"
                                class="rp-input rp-focusable"
                                type="text"
                                name="user_name"
                                autocomplete="username"
                                required
                                :aria-invalid="fieldInvalid('user_name')"
                            />
                            <p v-if="fieldError('user_name')" class="rp-error-text" role="alert">
                                {{ fieldError('user_name') }}
                            </p>
                        </div>
                        <div>
                            <label class="rp-label" for="reg-email">Електронна пошта</label>
                            <input
                                id="reg-email"
                                v-model="registerForm.email"
                                class="rp-input rp-focusable"
                                type="email"
                                name="email"
                                autocomplete="email"
                                required
                                :aria-invalid="fieldInvalid('email')"
                            />
                            <p v-if="fieldError('email')" class="rp-error-text" role="alert">
                                {{ fieldError('email') }}
                            </p>
                        </div>
                        <div>
                            <label class="rp-label" for="reg-password">Пароль</label>
                            <input
                                id="reg-password"
                                v-model="registerForm.password"
                                class="rp-input rp-focusable"
                                type="password"
                                name="password"
                                autocomplete="new-password"
                                required
                                :aria-invalid="fieldInvalid('password')"
                            />
                            <p class="rp-hint">Мінімум 8 символів.</p>
                            <p v-if="fieldError('password')" class="rp-error-text" role="alert">
                                {{ fieldError('password') }}
                            </p>
                        </div>
                        <div>
                            <label class="rp-label" for="reg-password_confirmation">Підтвердження пароля</label>
                            <input
                                id="reg-password_confirmation"
                                v-model="registerForm.password_confirmation"
                                class="rp-input rp-focusable"
                                type="password"
                                name="password_confirmation"
                                autocomplete="new-password"
                                required
                            />
                            <p
                                v-if="fieldError('password_confirmation')"
                                class="rp-error-text"
                                role="alert"
                            >
                                {{ fieldError('password_confirmation') }}
                            </p>
                        </div>
                        <button
                            type="submit"
                            class="rp-focusable rp-btn rp-btn-primary w-full"
                            :disabled="loading"
                        >
                            Зареєструватися
                        </button>
                    </form>
                </div>

                <div v-if="!user" class="mt-8">
                    <div class="rp-divider" aria-hidden="true">
                        або
                    </div>
                    <h3 class="rp-sr-only">Анонімний вхід</h3>
                    <p class="text-center text-sm text-[var(--rp-text-muted)]">
                        Швидкий вхід без облікового запису
                    </p>
                    <div class="mt-4">
                        <label class="rp-label" for="guest-user_name">
                            Нік (необов’язково)
                        </label>
                        <input
                            id="guest-user_name"
                            v-model="guestNick"
                            class="rp-input rp-focusable"
                            type="text"
                            autocomplete="off"
                            :aria-invalid="fieldInvalid('user_name')"
                        />
                        <p v-if="guestFieldError" class="rp-error-text" role="alert">
                            {{ guestFieldError }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-primary mt-4 w-full"
                        :disabled="loading"
                        @click="submitGuest"
                    >
                        Зайти анонімно
                    </button>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
const THEME_KEY = 'redpanda-theme';

export default {
    name: 'AuthWelcome',
    data() {
        return {
            appName: 'Чат Рудої Панди',
            mode: 'login',
            loading: false,
            user: null,
            formError: '',
            fieldErrors: {},
            guestFieldError: '',
            guestNick: '',
            loginForm: {
                user_name: '',
                password: '',
                remember: false,
            },
            registerForm: {
                user_name: '',
                email: '',
                password: '',
                password_confirmation: '',
            },
            themeUi: 'system',
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
    async mounted() {
        await this.refreshUser();
        this.maybeRedirectHistory();
    },
    methods: {
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
            this.guestFieldError = '';
        },
        setMode(m) {
            this.mode = m;
            this.clearErrors();
        },
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
        async refreshUser() {
            try {
                const { data } = await window.axios.get('/api/v1/auth/user');
                this.user = data.data;
            } catch {
                this.user = null;
            }
        },
        maybeRedirectHistory() {
            const h = this.$route.query.history;
            if ((h === '1' || h === 1) && this.user) {
                this.$router.replace({ name: 'archive' }).catch(() => {});
            }
        },
        handleAxiosError(err) {
            const status = err.response?.status;
            if (status === 422) {
                this.fieldErrors = err.response.data.errors || {};
                this.formError = err.response.data.message || 'Перевірте поля форми.';

                return;
            }
            if (status === 429) {
                this.formError = 'Забагато спроб. Зачекайте хвилину й спробуйте знову.';

                return;
            }
            this.formError = 'Сталася помилка мережі або сервера. Спробуйте пізніше.';
        },
        async submitLogin() {
            this.clearErrors();
            this.loading = true;
            try {
                await this.ensureSanctum();
                await window.axios.post('/api/v1/auth/login', {
                    user_name: this.loginForm.user_name,
                    password: this.loginForm.password,
                    remember: this.loginForm.remember,
                });
                await this.refreshUser();
                this.maybeRedirectHistory();
            } catch (e) {
                this.handleAxiosError(e);
            } finally {
                this.loading = false;
            }
        },
        async submitRegister() {
            this.clearErrors();
            this.loading = true;
            try {
                await this.ensureSanctum();
                await window.axios.post('/api/v1/auth/register', { ...this.registerForm });
                await this.refreshUser();
                this.maybeRedirectHistory();
            } catch (e) {
                this.handleAxiosError(e);
            } finally {
                this.loading = false;
            }
        },
        async submitGuest() {
            this.clearErrors();
            this.loading = true;
            try {
                await this.ensureSanctum();
                const payload = {};
                if (this.guestNick.trim()) {
                    payload.user_name = this.guestNick.trim();
                }
                await window.axios.post('/api/v1/auth/guest', payload);
                await this.refreshUser();
                this.maybeRedirectHistory();
            } catch (e) {
                if (e.response?.status === 422) {
                    const errs = e.response.data.errors || {};
                    this.guestFieldError = (errs.user_name && errs.user_name[0]) || e.response.data.message;
                } else {
                    this.handleAxiosError(e);
                }
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            this.loading = true;
            try {
                await this.ensureSanctum();
                await window.axios.post('/api/v1/auth/logout');
                this.user = null;
            } catch {
                this.formError = 'Не вдалося вийти. Спробуйте ще раз.';
            } finally {
                this.loading = false;
            }
        },
    },
};
</script>
