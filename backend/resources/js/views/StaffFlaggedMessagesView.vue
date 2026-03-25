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
                    <h1 class="text-lg font-semibold text-[var(--rp-text)] sm:text-xl">
                        Черга на модерацію
                    </h1>
                    <p class="mt-0.5 text-sm text-[var(--rp-text-muted)]">
                        Повідомлення з прапорцем після стоп-слів (T53). Доступно модераторам і адміністратору.
                    </p>
                </div>
            </div>
            <RpButton
                variant="ghost"
                class="text-sm"
                aria-label="Перемкнути тему оформлення"
                @click="cycleTheme"
            >
                {{ themeLabel }}
            </RpButton>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-5xl flex-1 space-y-4" tabindex="-1">
            <RpBanner v-if="!viewerIsStaff">
                Доступ лише для персоналу чату (модератор або адміністратор).
            </RpBanner>

            <template v-else>
                <RpPanel class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                    <div class="flex min-w-[8rem] flex-1 flex-col gap-1">
                        <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="fm-room">
                            Кімната (id, опційно)
                        </label>
                        <RpTextField
                            id="fm-room"
                            v-model="filterRoomId"
                            type="text"
                            inputmode="numeric"
                            placeholder="усі"
                            described-by="fm-room-hint"
                            autocomplete="off"
                        />
                        <p id="fm-room-hint" class="text-xs text-[var(--rp-text-muted)]">
                            Залиште порожнім, щоб показати всі кімнати.
                        </p>
                    </div>
                    <RpButton class="shrink-0" :disabled="loading" @click="loadPage(1)">
                        Застосувати фільтр
                    </RpButton>
                    <RpButton variant="ghost" class="shrink-0" :disabled="loading" @click="loadPage(meta.current_page)">
                        Оновити
                    </RpButton>
                </RpPanel>

                <RpBanner v-if="loadError">
                    {{ loadError }}
                </RpBanner>
                <p
                    v-if="statusMsg"
                    class="text-sm text-[var(--rp-text-muted)]"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    {{ statusMsg }}
                </p>
                <p v-if="loading" class="text-sm text-[var(--rp-text-muted)]" role="status">
                    Завантаження…
                </p>

                <p
                    v-else-if="rows.length === 0"
                    class="rounded-md border border-dashed border-[var(--rp-border-subtle)] px-4 py-8 text-center text-sm text-[var(--rp-text-muted)]"
                >
                    Немає повідомлень у черзі.
                </p>

                <div
                    v-else
                    class="mt-4 overflow-x-auto rounded-md border border-[var(--rp-border-subtle)]"
                >
                    <table class="w-full min-w-[48rem] border-collapse text-left text-sm text-[var(--rp-text)]">
                        <thead class="bg-[var(--rp-surface-elevated)] text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                            <tr>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Кімната
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Автор
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">Уривок</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Прапорець
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    <span class="rp-sr-only">Дії</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="r in rows"
                                :key="r.post_id"
                                class="border-b border-[var(--rp-border-subtle)] last:border-b-0"
                            >
                                <td class="px-3 py-2 align-top">
                                    <span class="font-medium">{{ r.room_name || '—' }}</span>
                                    <span class="mt-0.5 block text-xs text-[var(--rp-text-muted)]"
                                    >#{{ r.post_roomid }}</span>
                                </td>
                                <td class="px-3 py-2 align-top">
                                    {{ r.author_name || '—' }}
                                    <span
                                        v-if="r.is_deleted"
                                        class="mt-0.5 block text-xs text-[var(--rp-text-muted)]"
                                    >Видалено</span>
                                </td>
                                <td class="max-w-xs px-3 py-2 align-top">
                                    <span class="line-clamp-3 break-words">{{ r.snippet || '—' }}</span>
                                </td>
                                <td class="whitespace-nowrap px-3 py-2 align-top text-xs text-[var(--rp-text-muted)]">
                                    {{ formatFlagAt(r.moderation_flag_at) }}
                                </td>
                                <td class="px-3 py-2 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <RpButton variant="ghost" class="text-xs" @click="openInChat(r)">
                                            Відкрити в чаті
                                        </RpButton>
                                        <RpButton
                                            class="text-xs"
                                            :loading="clearingId === r.post_id"
                                            :disabled="clearingId === r.post_id"
                                            @click="clearFlag(r)"
                                        >
                                            Зняти прапорець
                                        </RpButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav
                    v-if="rows.length > 0 && meta.last_page > 1"
                    class="flex flex-wrap items-center justify-between gap-2 text-sm text-[var(--rp-text-muted)]"
                    aria-label="Пагінація черги"
                >
                    <span>Сторінка {{ meta.current_page }} з {{ meta.last_page }}</span>
                    <div class="flex gap-2">
                        <RpButton
                            variant="ghost"
                            class="text-xs"
                            :disabled="loading || meta.current_page <= 1"
                            @click="loadPage(meta.current_page - 1)"
                        >
                            Назад
                        </RpButton>
                        <RpButton
                            variant="ghost"
                            class="text-xs"
                            :disabled="loading || meta.current_page >= meta.last_page"
                            @click="loadPage(meta.current_page + 1)"
                        >
                            Далі
                        </RpButton>
                    </div>
                </nav>
            </template>
        </main>
    </div>
</template>

<script>
import { getResolvedTheme, THEME_KEY } from '../chat/chatRoomConstants';
import { isStaffRole } from '../lib/userBadgeMenuItems';

export default {
    name: 'StaffFlaggedMessagesView',
    data() {
        return {
            user: null,
            themeUi: 'system',
            loading: false,
            loadError: '',
            statusMsg: '',
            rows: [],
            filterRoomId: '',
            clearingId: null,
            meta: {
                current_page: 1,
                last_page: 1,
                per_page: 25,
                total: 0,
            },
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
        viewerIsStaff() {
            return this.user && isStaffRole(this.user.chat_role);
        },
        roomQuery() {
            const r = this.$route.query.room;

            return r != null && r !== '' ? { room: String(r) } : {};
        },
    },
    created() {
        this.themeUi = getResolvedTheme();
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
        formatFlagAt(ts) {
            const n = Number(ts);
            if (!n || n < 1) {
                return '—';
            }
            try {
                return new Date(n * 1000).toLocaleString('uk-UA');
            } catch {
                return String(ts);
            }
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
            if (!this.viewerIsStaff) {
                await this.$router.replace({ name: 'chat', query: this.roomQuery }).catch(() => {});

                return;
            }
            await this.loadPage(1);
        },
        async loadPage(page) {
            this.loading = true;
            this.loadError = '';
            try {
                const params = { page, per_page: 25 };
                const rid = String(this.filterRoomId || '').trim();
                if (rid !== '' && !Number.isNaN(Number(rid))) {
                    params.room_id = Number(rid);
                }
                const { data } = await window.axios.get('/api/v1/mod/flagged-messages', { params });
                this.rows = Array.isArray(data.data) ? data.data : [];
                const m = data.meta || {};
                this.meta = {
                    current_page: m.current_page || 1,
                    last_page: m.last_page || 1,
                    per_page: m.per_page || 25,
                    total: m.total || 0,
                };
                this.statusMsg = '';
            } catch (e) {
                this.rows = [];
                this.loadError =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося завантажити чергу.';
            } finally {
                this.loading = false;
            }
        },
        openInChat(row) {
            this.$router
                .push({
                    name: 'chat',
                    query: {
                        room: String(row.post_roomid),
                        focus_post: String(row.post_id),
                    },
                })
                .catch(() => {});
        },
        async clearFlag(row) {
            this.statusMsg = '';
            this.clearingId = row.post_id;
            try {
                await window.axios.patch(`/api/v1/mod/flagged-messages/${row.post_id}`);
                this.statusMsg = 'Прапорець знято.';
                await this.loadPage(this.meta.current_page);
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося зняти прапорець.';
            } finally {
                this.clearingId = null;
            }
        },
    },
};
</script>
