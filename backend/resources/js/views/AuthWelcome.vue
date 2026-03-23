<template>
    <div class="flex min-h-screen flex-col px-4 py-8 sm:px-6">
        <header
            class="mx-auto mb-8 flex w-full max-w-5xl items-center justify-between gap-4"
        >
            <div>
                <h1 class="text-xl font-bold tracking-tight text-[var(--rp-text)] sm:text-2xl">
                    {{ displayTitle }}
                </h1>
                <p class="mt-1 text-sm text-[var(--rp-text-muted)]">
                    {{ displayTagline }}
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
            class="mx-auto w-full max-w-5xl flex-1"
            tabindex="-1"
        >
            <div
                class="grid gap-8 lg:grid-cols-2 lg:items-start"
            >
            <div
                class="rp-panel min-w-0"
                role="region"
                :aria-labelledby="authRegionLabelledBy"
            >
                <template v-if="!user">
                <div
                    class="mb-6 flex flex-wrap gap-2"
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
                        v-if="registrationOpen"
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
                        v-else-if="registrationOpen"
                        class="mt-4 space-y-4"
                        novalidate
                        @submit.prevent="submitRegister"
                    >
                        <p
                            v-if="registrationMinAge != null"
                            class="text-sm text-[var(--rp-text-muted)]"
                        >
                            Мінімальний вік для реєстрації: {{ registrationMinAge }}.
                        </p>
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

                    <p
                        v-else
                        class="mt-4 text-sm text-[var(--rp-text-muted)]"
                        role="status"
                    >
                        Реєстрацію тимчасово вимкнено адміністратором.
                    </p>
                </div>

                <div class="mt-8">
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
                <p v-if="showSocialLoginButtons" class="mt-6 text-center text-xs text-[var(--rp-text-muted)]">
                    Соціальний вхід з’явиться після підключення Auth0 (T76).
                </p>
                </template>
                <p
                    v-else
                    class="py-6 text-center text-sm text-[var(--rp-text-muted)]"
                    aria-live="polite"
                >
                    Перенаправлення до чату…
                </p>
            </div>

            <aside
                v-if="hasLandingAside"
                class="rp-panel min-w-0 space-y-4 p-4 lg:p-5"
                aria-label="Новини та посилання"
            >
                <div v-if="landingNewsTitle || landingNewsBody">
                    <h2 class="text-base font-semibold text-[var(--rp-text)]">
                        {{ landingNewsTitle || 'Новини' }}
                    </h2>
                    <div
                        v-if="landingNewsBody"
                        class="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-[var(--rp-text-muted)]"
                    >
                        {{ landingNewsBody }}
                    </div>
                </div>
                <nav v-if="landingLinks.length" aria-label="Посилання з вітальні">
                    <ul class="space-y-2 text-sm">
                        <li v-for="(item, i) in landingLinks" :key="'land-link-' + i">
                            <a
                                :href="item.url"
                                class="rp-focusable font-medium text-[var(--rp-text)] underline decoration-[var(--rp-border-subtle)] underline-offset-2 hover:decoration-[var(--rp-text-muted)]"
                            >
                                {{ item.label || item.url }}
                            </a>
                        </li>
                    </ul>
                </nav>
            </aside>
            </div>

            <p
                v-if="!user"
                class="mx-auto mt-8 max-w-5xl text-center text-sm text-[var(--rp-text-muted)]"
                role="status"
            >
                Користувачі онлайн у чаті: <strong class="font-semibold text-[var(--rp-text)]">{{ usersOnline }}</strong>
            </p>
        </main>
    </div>
</template>

<script>
const THEME_KEY = 'redpanda-theme';
const LANDING_POLL_MS = 45000;

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
            landing: null,
            registration: null,
            usersOnline: 0,
            landingPollTimer: null,
        };
    },
    computed: {
        displayTitle() {
            const t = this.landing && this.landing.page_title;

            return t && String(t).trim() ? String(t).trim() : this.appName;
        },
        displayTagline() {
            const t = this.landing && this.landing.tagline;

            return t && String(t).trim() ? String(t).trim() : 'Український онлайн-чат';
        },
        landingNewsTitle() {
            return this.landing && this.landing.news_title ? String(this.landing.news_title).trim() : '';
        },
        landingNewsBody() {
            return this.landing && this.landing.news_body ? String(this.landing.news_body).trim() : '';
        },
        landingLinks() {
            const links = this.landing && Array.isArray(this.landing.links) ? this.landing.links : [];

            return links.filter((l) => l && (String(l.label || '').trim() || String(l.url || '').trim()));
        },
        hasLandingAside() {
            return Boolean(this.landingNewsTitle || this.landingNewsBody || this.landingLinks.length);
        },
        registrationOpen() {
            return !this.registration || this.registration.registration_open !== false;
        },
        registrationMinAge() {
            const n = this.registration && this.registration.min_age;

            return n != null && n !== '' && !Number.isNaN(Number(n)) ? Number(n) : null;
        },
        showSocialLoginButtons() {
            return Boolean(this.registration && this.registration.show_social_login_buttons);
        },
        authRegionLabelledBy() {
            if (this.user) {
                return undefined;
            }

            return this.mode === 'login' ? 'login-heading' : 'register-heading';
        },
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
        this.fetchLandingPublic();
        this.startLandingPoll();
        await this.refreshUser();
        this.redirectIfAuthenticated();
    },
    beforeDestroy() {
        this.stopLandingPoll();
    },
    watch: {
        registrationOpen(open) {
            if (!open && this.mode === 'register') {
                this.setMode('login');
            }
        },
    },
    methods: {
        startLandingPoll() {
            this.stopLandingPoll();
            this.landingPollTimer = window.setInterval(() => {
                if (!this.user) {
                    this.fetchLandingPublic();
                }
            }, LANDING_POLL_MS);
        },
        stopLandingPoll() {
            if (this.landingPollTimer != null) {
                clearInterval(this.landingPollTimer);
                this.landingPollTimer = null;
            }
        },
        async fetchLandingPublic() {
            try {
                const { data } = await window.axios.get('/api/v1/landing');
                const d = data && data.data;
                if (!d) {
                    return;
                }
                this.landing = d.landing && typeof d.landing === 'object' ? d.landing : null;
                this.registration = d.registration && typeof d.registration === 'object' ? d.registration : null;
                this.usersOnline = Number(d.users_online) >= 0 ? Number(d.users_online) : 0;
            } catch {
                /* залишаємо попередні значення / дефолти */
            }
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
        redirectIfAuthenticated() {
            if (!this.user) {
                return;
            }
            const h = this.$route.query.history;
            if (h === '1' || h === 1) {
                this.$router.replace({ name: 'archive' }).catch((err) => {
                    if (import.meta.env.DEV) {
                        console.warn('[AuthWelcome] redirect to archive failed', err);
                    }
                });

                return;
            }
            this.$router.replace({ name: 'chat' }).catch((err) => {
                if (import.meta.env.DEV) {
                    console.warn('[AuthWelcome] redirect to chat failed', err);
                }
            });
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
                this.redirectIfAuthenticated();
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
                this.redirectIfAuthenticated();
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
                this.redirectIfAuthenticated();
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
    },
};
</script>
