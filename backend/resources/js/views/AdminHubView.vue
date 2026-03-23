<template>
    <div class="flex min-h-screen flex-col bg-[var(--rp-bg)] px-4 py-6 sm:px-6">
        <header class="mx-auto mb-6 flex w-full max-w-5xl flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <router-link
                    :to="{ name: 'chat', query: roomQuery }"
                    class="rp-focusable shrink-0 text-sm font-medium text-[var(--rp-link)] hover:text-[var(--rp-link-hover)]"
                >
                    ← До чату
                </router-link>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-[var(--rp-text)] sm:text-xl">Панель адміна</h1>
                    <p class="mt-0.5 text-sm text-[var(--rp-text-muted)]">
                        Категорії налаштувань та інструментів — у стилі legacy board.te.ua (T75).
                    </p>
                </div>
            </div>
            <RpButton variant="ghost" class="text-sm" aria-label="Перемкнути тему оформлення" @click="cycleTheme">
                {{ themeLabel }}
            </RpButton>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-5xl flex-1" tabindex="-1">
            <RpBanner v-if="!viewerIsAdmin">
                Доступ лише для адміністратора чату.
                <router-link :to="{ name: 'chat', query: roomQuery }" class="rp-focusable ml-1 underline">
                    Повернутися до чату
                </router-link>
            </RpBanner>

            <template v-else>
                <p id="admin-hub-desc" class="sr-only">
                    Оберіть розділ: налаштування чату, користувачі, модерація або архів.
                </p>
                <ul
                    class="grid list-none gap-4 sm:grid-cols-2"
                    role="list"
                    aria-describedby="admin-hub-desc"
                >
                    <li v-for="card in hubCards" :key="card.id">
                        <router-link
                            :to="card.to"
                            class="rp-focusable flex h-full flex-col rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] p-4 shadow-sm transition-colors hover:border-[var(--rp-border)] hover:bg-[var(--rp-surface-elevated)]"
                        >
                            <span class="text-base font-semibold text-[var(--rp-text)]">{{ card.title }}</span>
                            <span class="mt-1 text-sm text-[var(--rp-text-muted)]">{{ card.description }}</span>
                        </router-link>
                    </li>
                </ul>
            </template>
        </main>
    </div>
</template>

<script>
import RpBanner from '../components/ui/RpBanner.vue';

const THEME_KEY = 'redpanda-theme';

export default {
    name: 'AdminHubView',
    components: { RpBanner },
    data() {
        return {
            user: null,
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
        viewerIsAdmin() {
            return this.user && this.user.chat_role === 'admin';
        },
        roomQuery() {
            const r = this.$route.query.room;

            return r != null && r !== '' ? { room: String(r) } : {};
        },
        hubCards() {
            const qBase = this.roomQuery;
            const withRoom = (extra = {}) => ({ ...qBase, ...extra });

            return [
                {
                    id: 'settings',
                    title: 'Налаштування чату',
                    description: 'Вітальня, реєстрація, пороги кімнат, slash, звук на кожен пост…',
                    to: { name: 'chat', query: withRoom({ open_chat_settings: '1' }) },
                },
                {
                    id: 'users',
                    title: 'Користувачі (персонал)',
                    description: 'Пошук, ролі, профілі, масові дії',
                    to: { name: 'staff-users', query: qBase },
                },
                {
                    id: 'stop-words',
                    title: 'Стоп-слова та фільтр',
                    description: 'Правила для публічних повідомлень',
                    to: { name: 'staff-stop-words', query: qBase },
                },
                {
                    id: 'flagged',
                    title: 'Черга на модерацію',
                    description: 'Повідомлення на перевірку',
                    to: { name: 'staff-flagged', query: qBase },
                },
                {
                    id: 'archive',
                    title: 'Архів чату',
                    description: 'Перегляд історії повідомлень',
                    to: { name: 'archive', query: qBase },
                },
            ];
        },
    },
    watch: {
        '$route.query.room'() {
            /* roomQuery computed */
        },
    },
    created() {
        this.themeUi = localStorage.getItem(THEME_KEY) || 'system';
    },
    async mounted() {
        document.documentElement.setAttribute('data-theme', this.themeUi);
        await this.bootstrap();
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
        async fetchUser() {
            try {
                const { data } = await window.axios.get('/api/v1/auth/user');

                return data.data;
            } catch {
                return null;
            }
        },
        async bootstrap() {
            this.user = await this.fetchUser();
            if (!this.user) {
                await this.$router.replace({ path: '/' });

                return;
            }
            if (!this.viewerIsAdmin) {
                return;
            }
        },
    },
};
</script>
