<template>
    <div
        class="rp-auth-landing rp-auth-landing--welcome-board flex min-h-0 flex-1 flex-col px-4 py-8 pb-12 sm:px-6 sm:py-10"
    >
        <main
            id="main-content"
            class="mx-auto w-full max-w-5xl flex-1"
            tabindex="-1"
        >
            <header class="rp-auth-landing-hero">
                <div class="rp-auth-landing-hero-inner">
                    <a
                        href="/"
                        class="rp-auth-landing-logo-link rp-auth-landing-hero-logo-link rp-focusable"
                        aria-label="На головну чату"
                    >
                        <img
                            class="rp-auth-landing-logo rp-auth-landing-hero-logo"
                            :src="landingLogoUrl"
                            width="128"
                            height="128"
                            alt="Логотип чату — руда панда"
                        />
                    </a>
                    <div class="rp-auth-landing-brand-titles rp-auth-landing-hero-titles">
                        <h1>{{ displayTitle }}</h1>
                        <p class="rp-auth-landing-tagline">
                            {{ displayTagline }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="rp-auth-landing-card rp-auth-landing-card--welcome-board">
                <div
                    class="rp-auth-landing-main-grid grid gap-0 lg:grid-cols-2 lg:items-stretch"
                >
            <div
                class="rp-auth-landing-form-col min-w-0 p-5 lg:p-8"
                role="region"
                :aria-labelledby="authRegionLabelledBy"
            >
                <template v-if="!user">
                <div
                    class="rp-auth-welcome-tablist mb-6 flex gap-0"
                    role="tablist"
                    aria-label="Вхід або реєстрація"
                    @keydown="onAuthTabKeydown"
                >
                    <button
                        id="tab-login"
                        type="button"
                        role="tab"
                        class="rp-auth-welcome-tab rp-focusable"
                        :tabindex="mode === 'login' ? 0 : -1"
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
                        class="rp-auth-welcome-tab rp-focusable"
                        :tabindex="mode === 'register' ? 0 : -1"
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
                    <h3 id="login-heading" class="rp-sr-only">Вхід</h3>
                    <h3 id="register-heading" class="rp-sr-only">Реєстрація</h3>

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
                        <p class="rp-auth-landing-forgot">
                            <router-link
                                to="/forgot-password"
                                class="rp-auth-landing-forgot-btn rp-focusable inline-block text-sm"
                            >
                                Забули пароль?
                            </router-link>
                        </p>
                        <label class="rp-auth-welcome-remember flex cursor-pointer items-center gap-2 text-sm">
                            <input
                                v-model="loginForm.remember"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border-[var(--rp-border-subtle)]"
                            />
                            Запам’ятати мене
                        </label>
                        <RpButton
                            native-type="submit"
                            class="rp-auth-welcome-submit w-full"
                            :loading="loading"
                            :disabled="loading"
                        >
                            Увійти
                        </RpButton>
                    </form>

                    <form
                        v-else-if="registrationOpen"
                        class="relative mt-4 space-y-4"
                        novalidate
                        @submit.prevent="submitRegister"
                    >
                        <p
                            v-if="registrationMinAge != null"
                            class="text-sm text-[var(--rp-text-muted)]"
                        >
                            Мінімальний вік для реєстрації: {{ registrationMinAge }} (підказка; підтвердження віку на сервері не вимагається).
                        </p>
                        <div class="rp-honeypot" aria-hidden="true">
                            <label class="rp-label" for="reg-department">Відділ</label>
                            <input
                                id="reg-department"
                                v-model="registerForm.department"
                                type="text"
                                name="department"
                                tabindex="-1"
                                autocomplete="off"
                            />
                        </div>
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
                        <RpButton
                            native-type="submit"
                            class="rp-auth-welcome-submit w-full"
                            :loading="loading"
                            :disabled="loading"
                        >
                            Зареєструватися
                        </RpButton>
                    </form>

                    <p
                        v-else
                        class="mt-4 text-sm text-[var(--rp-text-muted)]"
                        role="status"
                    >
                        Реєстрацію тимчасово вимкнено адміністратором.
                    </p>
                </div>
                <div
                    v-if="socialLoginUiVisible"
                    class="rp-auth-welcome-social mt-6 flex flex-col gap-2"
                    role="group"
                    aria-label="Вхід через соціальні мережі"
                >
                    <RpButton
                        variant="ghost"
                        class="rp-auth-welcome-social-fb w-full"
                        :disabled="loading"
                        @click="startSocialLogin('facebook')"
                    >
                        <span class="rp-auth-welcome-social-fb-icon" aria-hidden="true">f</span>
                        Увійти з facebook
                    </RpButton>
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-stretch">
                        <RpButton
                            variant="ghost"
                            class="rp-auth-welcome-social-alt w-full sm:flex-1"
                            :disabled="loading"
                            @click="startSocialLogin('google-oauth2')"
                        >
                            Google
                        </RpButton>
                        <RpButton
                            variant="ghost"
                            class="rp-auth-welcome-social-alt w-full sm:flex-1"
                            :disabled="loading"
                            @click="startSocialLogin('twitter')"
                        >
                            X
                        </RpButton>
                    </div>
                </div>
                <div class="mt-8">
                    <div class="rp-divider" aria-hidden="true">
                        Швидкий вхід без облікового запису
                    </div>
                    <h4 class="rp-sr-only">Анонімний вхід</h4>
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
                    <RpButton
                        class="rp-auth-welcome-guest mt-4 w-full"
                        :loading="loading"
                        :disabled="loading"
                        @click="submitGuest"
                    >
                        Зайти анонімно
                    </RpButton>
                </div>
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
                class="rp-auth-landing-aside rp-auth-landing-aside--welcome-board flex min-w-0 flex-col space-y-4 p-5 lg:p-8"
                aria-label="Новини та посилання"
            >
                <div class="min-h-0 flex-1 space-y-4">
                    <div>
                        <h3 class="rp-auth-welcome-aside-title text-base font-semibold">
                            {{ asideNewsTitle }}
                        </h3>
                        <div
                            class="rp-auth-welcome-aside-body mt-2 whitespace-pre-wrap text-sm leading-relaxed"
                        >
                            {{ asideNewsBody }}
                        </div>
                    </div>
                    <nav v-if="landingLinks.length" aria-label="Посилання з вітальні">
                        <ul class="rp-auth-welcome-links space-y-2 text-sm">
                            <li v-for="(item, i) in landingLinks" :key="'land-link-' + i">
                                <a
                                    :href="item.url"
                                    class="rp-auth-welcome-link rp-focusable font-medium"
                                >
                                    {{ item.label || item.url }}
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
                <p
                    v-if="!user"
                    class="rp-auth-welcome-aside-online mt-auto pt-4"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    Користувачі онлайн
                    <strong>{{ usersOnline }}</strong>
                </p>
            </aside>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
import { cacheAuth0FromLandingPayload, ensureAuth0Client } from '../lib/rpAuth0';
import { CHAT_BRAND_TITLE, sanitizeTitleSegment } from '../utils/chatDocumentTitle';

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
                department: '',
            },
            landing: null,
            registration: null,
            usersOnline: 0,
            landingPollTimer: null,
            /** Публічний асет `public/brand/` — не статичний src у шаблоні (Vite). */
            landingLogoUrl: '/brand/board-te-ua-orange.png',
            /** Публічні поля Auth0 з GET /api/v1/landing (T76). */
            auth0Public: null,
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
        asideNewsTitle() {
            return this.landingNewsTitle || 'Новини та оголошення';
        },
        asideNewsBody() {
            if (this.landingNewsBody) {
                return this.landingNewsBody;
            }

            return 'Тут з’являтимуться текст і посилання, які налаштовує адміністратор у розділі налаштувань чату. Оберіть спосіб входу ліворуч, щоб приєднатися до розмови.';
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
        socialLoginUiVisible() {
            const a0 = this.auth0Public;
            if (!a0 || !a0.enabled) {
                return false;
            }
            if (this.registration && this.registration.show_social_login_buttons === false) {
                return false;
            }

            return true;
        },
        authRegionLabelledBy() {
            if (this.user) {
                return undefined;
            }

            return this.mode === 'login' ? 'login-heading' : 'register-heading';
        },
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
        displayTitle: {
            immediate: true,
            handler() {
                this.syncHomeDocumentTitle();
            },
        },
        '$route.name'() {
            this.syncHomeDocumentTitle();
        },
    },
    methods: {
        /** T93 — канонічний title вітальні (адмінський page_title або бренд). */
        syncHomeDocumentTitle() {
            if (typeof document === 'undefined') {
                return;
            }
            if (!this.$route || this.$route.name !== 'home') {
                return;
            }
            const t = sanitizeTitleSegment(this.displayTitle, 160);

            document.title = t || CHAT_BRAND_TITLE;
        },
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
                this.auth0Public = d.auth0 && typeof d.auth0 === 'object' ? d.auth0 : null;
                cacheAuth0FromLandingPayload(this.auth0Public);
                if (this.auth0Public && this.auth0Public.enabled) {
                    ensureAuth0Client().catch(() => {});
                }
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
            this.$nextTick(() => {
                const id = m === 'login' ? 'tab-login' : 'tab-register';
                const el = document.getElementById(id);
                if (el && typeof el.focus === 'function') {
                    try {
                        el.focus();
                    } catch {
                        /* */
                    }
                }
            });
        },
        /** T133: WAI-ARIA Tabs — стрілки та Home/End між «Вхід» і «Реєстрація». */
        onAuthTabKeydown(e) {
            if (!this.registrationOpen) {
                return;
            }
            const keys = ['ArrowRight', 'ArrowLeft', 'ArrowDown', 'ArrowUp', 'Home', 'End'];
            if (!keys.includes(e.key)) {
                return;
            }
            e.preventDefault();
            let next = this.mode;
            if (e.key === 'Home') {
                next = 'login';
            } else if (e.key === 'End') {
                next = 'register';
            } else if (this.mode === 'login') {
                next = 'register';
            } else {
                next = 'login';
            }
            if (next !== this.mode) {
                this.setMode(next);
            }
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
            if (this.user.requires_password_setup) {
                this.$router.replace({ name: 'legacy-password-setup' }).catch((err) => {
                    if (import.meta.env.DEV) {
                        console.warn('[AuthWelcome] redirect to legacy password setup failed', err);
                    }
                });

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
            if (status === 403) {
                const msg = err.response?.data?.message;
                this.formError =
                    typeof msg === 'string' && msg.trim() !== ''
                        ? msg
                        : 'Цю дію зараз заборонено.';

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
        async startSocialLogin(connection) {
            this.clearErrors();
            this.loading = true;
            try {
                await this.ensureSanctum();
                const client = await ensureAuth0Client();
                if (!client) {
                    this.formError = 'Соціальний вхід тимчасово недоступний. Спробуйте пізніше.';

                    return;
                }
                await client.loginWithRedirect({
                    authorizationParams: { connection },
                });
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
