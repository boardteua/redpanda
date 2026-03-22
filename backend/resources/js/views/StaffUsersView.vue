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
                        Користувачі (персонал)
                    </h1>
                    <p class="mt-0.5 text-sm text-[var(--rp-text-muted)]">
                        Пошук, зміна VIP та рангу (адмін), редагування профілю за матрицею прав.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="rp-focusable rp-btn rp-btn-ghost text-sm"
                aria-label="Перемкнути тему оформлення"
                @click="cycleTheme"
            >
                {{ themeLabel }}
            </button>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-5xl flex-1 space-y-4" tabindex="-1">
            <div v-if="!isStaff" class="rp-banner" role="alert">
                Доступ лише для модераторів та адміністраторів.
            </div>

            <template v-else>
                <div class="rp-panel space-y-3">
                    <div class="flex flex-wrap gap-2">
                        <label class="rp-label rp-sr-only" for="staff-user-q">Пошук</label>
                        <input
                            id="staff-user-q"
                            v-model.trim="searchInput"
                            type="search"
                            maxlength="191"
                            class="rp-input rp-focusable min-w-[12rem] flex-1"
                            placeholder="Нік, id або e-mail (лише адмін)…"
                            :disabled="loading"
                            @keyup.enter="applySearch"
                        />
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-primary shrink-0"
                            :disabled="loading"
                            @click="applySearch"
                        >
                            Шукати
                        </button>
                    </div>
                    <p v-if="viewerIsAdmin" class="text-xs text-[var(--rp-text-muted)]">
                        Пошук за e-mail доступний лише адміністратору; модератор бачить нік та id.
                    </p>
                </div>

                <p v-if="loadError" class="rp-banner" role="alert">
                    {{ loadError }}
                </p>
                <p v-if="statusMsg" class="text-sm text-[var(--rp-text-muted)]" role="status">
                    {{ statusMsg }}
                </p>

                <p v-if="loading" class="text-sm text-[var(--rp-text-muted)]" role="status">
                    Завантаження…
                </p>

                <div v-else class="overflow-x-auto rounded-md border border-[var(--rp-border-subtle)]">
                    <table class="w-full min-w-[36rem] border-collapse text-left text-sm text-[var(--rp-text)]">
                        <thead class="bg-[var(--rp-surface-elevated)] text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                            <tr>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Id
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Нік
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Роль
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    VIP
                                </th>
                                <th
                                    v-if="viewerIsAdmin"
                                    scope="col"
                                    class="border-b border-[var(--rp-border-subtle)] px-3 py-2"
                                >
                                    E-mail
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="r in rows"
                                :key="r.id"
                                :class="[
                                    'cursor-pointer border-b border-[var(--rp-border-subtle)]',
                                    selected && selected.id === r.id ? 'bg-[var(--rp-surface-elevated)]' : '',
                                ]"
                                @click="selectRow(r)"
                            >
                                <td class="px-3 py-2 font-mono text-xs">
                                    {{ r.id }}
                                </td>
                                <td class="px-3 py-2 font-medium">
                                    {{ r.user_name }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ roleLabel(r.chat_role) }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ r.vip ? 'Так' : 'Ні' }}
                                </td>
                                <td v-if="viewerIsAdmin" class="max-w-[14rem] truncate px-3 py-2 text-xs">
                                    {{ r.email || '—' }}
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td
                                    :colspan="viewerIsAdmin ? 5 : 4"
                                    class="px-3 py-8 text-center text-[var(--rp-text-muted)]"
                                >
                                    Немає результатів. Введіть запит і натисніть «Шукати».
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav
                    v-if="meta && meta.last_page > 1"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--rp-border-subtle)] pt-4"
                    aria-label="Пагінація"
                >
                    <p class="text-sm text-[var(--rp-text-muted)]">
                        Сторінка {{ meta.current_page }} з {{ meta.last_page }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-ghost text-sm"
                            :disabled="loading || meta.current_page <= 1"
                            @click="goPage(meta.current_page - 1)"
                        >
                            Попередня
                        </button>
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-ghost text-sm"
                            :disabled="loading || meta.current_page >= meta.last_page"
                            @click="goPage(meta.current_page + 1)"
                        >
                            Наступна
                        </button>
                    </div>
                </nav>

                <div
                    v-if="selected"
                    class="rp-panel space-y-4 border border-[var(--rp-border-subtle)]"
                    role="region"
                    aria-label="Редагування обраного користувача"
                >
                    <h2 class="text-base font-semibold text-[var(--rp-text)]">
                        Редагування: {{ selected.user_name }} (id {{ selected.id }})
                    </h2>

                    <p v-if="!selected.can_manage" class="text-sm text-[var(--rp-text-muted)]">
                        Недостатньо прав змінювати цього користувача (вищий або рівний ранг).
                    </p>

                    <template v-else>
                        <div v-if="!selected.guest" class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                                Профіль
                            </p>
                            <div>
                                <label class="rp-label" for="staff-about">Про мене</label>
                                <textarea
                                    id="staff-about"
                                    v-model="draftAbout"
                                    rows="3"
                                    maxlength="5000"
                                    class="rp-input rp-focusable mt-1 w-full"
                                />
                            </div>
                            <div>
                                <label class="rp-label" for="staff-occupation">Рід занять</label>
                                <input
                                    id="staff-occupation"
                                    v-model="draftOccupation"
                                    type="text"
                                    maxlength="191"
                                    class="rp-input rp-focusable mt-1 w-full"
                                />
                            </div>
                            <template v-if="viewerIsAdmin">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="rp-label" for="staff-country">Країна</label>
                                        <input
                                            id="staff-country"
                                            v-model="draftCountry"
                                            type="text"
                                            maxlength="100"
                                            class="rp-input rp-focusable mt-1 w-full"
                                        />
                                    </div>
                                    <div>
                                        <label class="rp-label" for="staff-region">Регіон</label>
                                        <input
                                            id="staff-region"
                                            v-model="draftRegion"
                                            type="text"
                                            maxlength="100"
                                            class="rp-input rp-focusable mt-1 w-full"
                                        />
                                    </div>
                                </div>
                            </template>
                            <button
                                type="button"
                                class="rp-focusable rp-btn rp-btn-secondary text-sm"
                                :disabled="savingProfile"
                                @click="saveProfile"
                            >
                                Зберегти профіль
                            </button>
                        </div>
                        <p v-else class="text-sm text-[var(--rp-text-muted)]">
                            Профіль гостя тут не редагується.
                        </p>

                        <div class="space-y-2 border-t border-[var(--rp-border-subtle)] pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                                Ролі та VIP
                            </p>
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input
                                    v-model="draftVip"
                                    type="checkbox"
                                    class="rp-focusable h-4 w-4 rounded border border-[var(--rp-border-subtle)]"
                                    :disabled="savingRoles || selected.guest"
                                />
                                VIP
                            </label>
                            <div v-if="viewerIsAdmin" class="max-w-xs">
                                <label class="rp-label" for="staff-rank">Ранг (user_rank)</label>
                                <select
                                    id="staff-rank"
                                    v-model.number="draftRank"
                                    class="rp-input rp-focusable mt-1 w-full"
                                    :disabled="savingRoles"
                                >
                                    <option :value="0">
                                        Користувач (0)
                                    </option>
                                    <option :value="1">
                                        Модератор (1)
                                    </option>
                                    <option :value="2">
                                        Адміністратор (2)
                                    </option>
                                </select>
                            </div>
                            <button
                                type="button"
                                class="rp-focusable rp-btn rp-btn-primary text-sm"
                                :disabled="savingRoles || selected.guest"
                                @click="saveRoles"
                            >
                                Зберегти ролі
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </main>
    </div>
</template>

<script>
import { isStaffRole } from '../lib/userBadgeMenuItems';

const THEME_KEY = 'redpanda-theme';

export default {
    name: 'StaffUsersView',
    data() {
        return {
            user: null,
            themeUi: 'system',
            loading: false,
            loadError: '',
            statusMsg: '',
            rows: [],
            meta: null,
            page: 1,
            searchInput: '',
            appliedSearch: '',
            selected: null,
            draftVip: false,
            draftRank: 0,
            draftAbout: '',
            draftOccupation: '',
            draftCountry: '',
            draftRegion: '',
            savingRoles: false,
            savingProfile: false,
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
        isStaff() {
            return this.user && isStaffRole(this.user.chat_role);
        },
        viewerIsAdmin() {
            return this.user && this.user.chat_role === 'admin';
        },
        roomQuery() {
            const r = this.$route.query.room;

            return r != null && r !== '' ? { room: String(r) } : {};
        },
    },
    watch: {
        '$route.query.room'() {
            /* keep roomQuery computed in sync */
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
        roleLabel(role) {
            const map = {
                guest: 'Гість',
                user: 'Користувач',
                vip: 'VIP',
                moderator: 'Модератор',
                admin: 'Адміністратор',
            };

            return map[role] || role || '—';
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
            if (!this.isStaff) {
                return;
            }
            if (this.appliedSearch) {
                await this.loadPage(1);
            }
        },
        selectRow(r) {
            this.selected = r;
            this.draftVip = !!r.vip;
            this.draftRank = Number(r.user_rank) || 0;
            const p = r.profile || {};
            this.draftAbout = p.about != null ? String(p.about) : '';
            this.draftOccupation = p.occupation != null ? String(p.occupation) : '';
            this.draftCountry = p.country != null ? String(p.country) : '';
            this.draftRegion = p.region != null ? String(p.region) : '';
            this.statusMsg = '';
        },
        mergeRow(updated) {
            const i = this.rows.findIndex((x) => x.id === updated.id);
            if (i >= 0) {
                this.$set(this.rows, i, updated);
            }
            if (this.selected && this.selected.id === updated.id) {
                this.selectRow(updated);
            }
        },
        async loadPage(p) {
            if (!this.appliedSearch) {
                this.loadError = 'Спочатку введіть пошуковий запит.';

                return;
            }
            this.loading = true;
            this.loadError = '';
            try {
                const { data } = await window.axios.get('/api/v1/mod/users', {
                    params: { q: this.appliedSearch, page: p, per_page: 20 },
                });
                this.rows = Array.isArray(data.data) ? data.data : [];
                this.meta = data.meta || null;
                this.page = p;
                this.selected = null;
            } catch (e) {
                this.rows = [];
                this.meta = null;
                this.loadError =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося виконати пошук.';
            } finally {
                this.loading = false;
            }
        },
        applySearch() {
            this.appliedSearch = this.searchInput;
            this.page = 1;
            this.loadError = '';
            if (!this.appliedSearch) {
                this.loadError = 'Введіть непорожній запит.';

                return;
            }
            this.loadPage(1);
        },
        goPage(p) {
            if (!this.meta || p < 1 || p > this.meta.last_page) {
                return;
            }
            this.loadPage(p);
        },
        async saveRoles() {
            if (!this.selected || !this.selected.can_manage) {
                return;
            }
            this.savingRoles = true;
            this.statusMsg = '';
            try {
                const body = { vip: this.draftVip };
                if (this.viewerIsAdmin) {
                    body.user_rank = this.draftRank;
                }
                const { data } = await window.axios.patch(`/api/v1/mod/users/${this.selected.id}`, body);
                this.mergeRow(data.data);
                this.statusMsg = 'Ролі збережено.';
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) || 'Не вдалося зберегти ролі.';
            } finally {
                this.savingRoles = false;
            }
        },
        async saveProfile() {
            if (!this.selected || !this.selected.can_manage || this.selected.guest) {
                return;
            }
            this.savingProfile = true;
            this.statusMsg = '';
            try {
                const profile = {
                    about: this.draftAbout,
                    occupation: this.draftOccupation,
                };
                if (this.viewerIsAdmin) {
                    profile.country = this.draftCountry;
                    profile.region = this.draftRegion;
                }
                const { data } = await window.axios.patch(`/api/v1/mod/users/${this.selected.id}/profile`, {
                    profile,
                });
                this.mergeRow(data.data);
                this.statusMsg = 'Профіль збережено.';
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося зберегти профіль.';
            } finally {
                this.savingProfile = false;
            }
        },
    },
};
</script>
