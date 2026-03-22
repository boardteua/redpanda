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
                        Стоп-слова та фільтр
                    </h1>
                    <p class="mt-0.5 text-sm text-[var(--rp-text-muted)]">
                        Правила для публічних повідомлень у кімнатах — доступно модераторам і адміністратору.
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
            <div v-if="!viewerIsStaff" class="rp-banner" role="alert">
                Доступ лише для персоналу чату (модератор або адміністратор).
            </div>

            <template v-else>
                <div class="rp-panel space-y-3">
                    <h2 class="text-sm font-semibold text-[var(--rp-text)]">Нове правило</h2>
                    <form class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end" @submit.prevent="addRule">
                        <div class="flex min-w-[10rem] flex-1 flex-col gap-1">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-word">Слово / фраза</label>
                            <input
                                id="sw-word"
                                v-model.trim="createForm.word"
                                type="text"
                                maxlength="191"
                                class="rp-input rp-focusable"
                                required
                                autocomplete="off"
                            />
                        </div>
                        <div class="flex w-full min-w-[8rem] flex-col gap-1 sm:w-40">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-cat">Категорія</label>
                            <input
                                id="sw-cat"
                                v-model.trim="createForm.category"
                                type="text"
                                maxlength="64"
                                class="rp-input rp-focusable"
                                placeholder="default"
                                autocomplete="off"
                            />
                        </div>
                        <div class="flex w-full min-w-[10rem] flex-col gap-1 sm:w-48">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-mode">Збіг</label>
                            <select id="sw-mode" v-model="createForm.match_mode" class="rp-input rp-focusable">
                                <option value="substring">Підрядок</option>
                                <option value="whole_word">Ціле слово</option>
                            </select>
                        </div>
                        <div class="flex w-full min-w-[10rem] flex-col gap-1 sm:w-48">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-action">Дія</label>
                            <select id="sw-action" v-model="createForm.action" class="rp-input rp-focusable">
                                <option value="mask">Замаскувати</option>
                                <option value="reject">Відхилити</option>
                                <option value="flag">Позначити для модерації</option>
                                <option value="temp_mute">Мут + відхилити</option>
                            </select>
                        </div>
                        <div v-if="createForm.action === 'temp_mute'" class="flex w-full min-w-[6rem] flex-col gap-1 sm:w-28">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-min">Хв.</label>
                            <input
                                id="sw-min"
                                v-model.number="createForm.mute_minutes"
                                type="number"
                                min="1"
                                max="525600"
                                class="rp-input rp-focusable"
                                placeholder="30"
                            />
                        </div>
                        <button type="submit" class="rp-focusable rp-btn rp-btn-primary shrink-0" :disabled="saving">
                            Додати
                        </button>
                    </form>
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
                    <table class="w-full min-w-[42rem] border-collapse text-left text-sm text-[var(--rp-text)]">
                        <thead class="bg-[var(--rp-surface-elevated)] text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                            <tr>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">Id</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">Слово</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">Категорія</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">Збіг</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">Дія</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">Мут (хв.)</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2"></th>
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
                                <td class="px-3 py-2 font-mono text-xs">{{ r.id }}</td>
                                <td class="px-3 py-2 font-medium">{{ r.word }}</td>
                                <td class="px-3 py-2">{{ r.category }}</td>
                                <td class="px-3 py-2">{{ matchLabel(r.match_mode) }}</td>
                                <td class="px-3 py-2">{{ actionLabel(r.action) }}</td>
                                <td class="px-3 py-2">{{ r.mute_minutes != null ? r.mute_minutes : '—' }}</td>
                                <td class="px-3 py-2 text-right">
                                    <button
                                        type="button"
                                        class="rp-focusable text-xs font-medium text-[var(--rp-danger)] hover:underline"
                                        @click.stop="removeRow(r)"
                                    >
                                        Видалити
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <p v-if="rows.length === 0" class="px-3 py-4 text-sm text-[var(--rp-text-muted)]">
                        Правил ще немає.
                    </p>
                </div>

                <div v-if="selected" class="rp-panel space-y-3">
                    <h2 class="text-sm font-semibold text-[var(--rp-text)]">Редагувати #{{ selected.id }}</h2>
                    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                        <div class="flex min-w-[10rem] flex-1 flex-col gap-1">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-e-word">Слово</label>
                            <input
                                id="sw-e-word"
                                v-model.trim="editForm.word"
                                type="text"
                                maxlength="191"
                                class="rp-input rp-focusable"
                            />
                        </div>
                        <div class="flex w-full min-w-[8rem] flex-col gap-1 sm:w-40">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-e-cat">Категорія</label>
                            <input
                                id="sw-e-cat"
                                v-model.trim="editForm.category"
                                type="text"
                                maxlength="64"
                                class="rp-input rp-focusable"
                            />
                        </div>
                        <div class="flex w-full min-w-[10rem] flex-col gap-1 sm:w-48">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-e-mode">Збіг</label>
                            <select id="sw-e-mode" v-model="editForm.match_mode" class="rp-input rp-focusable">
                                <option value="substring">Підрядок</option>
                                <option value="whole_word">Ціле слово</option>
                            </select>
                        </div>
                        <div class="flex w-full min-w-[10rem] flex-col gap-1 sm:w-48">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-e-action">Дія</label>
                            <select id="sw-e-action" v-model="editForm.action" class="rp-input rp-focusable">
                                <option value="mask">Замаскувати</option>
                                <option value="reject">Відхилити</option>
                                <option value="flag">Позначити для модерації</option>
                                <option value="temp_mute">Мут + відхилити</option>
                            </select>
                        </div>
                        <div v-if="editForm.action === 'temp_mute'" class="flex w-full min-w-[6rem] flex-col gap-1 sm:w-28">
                            <label class="text-xs font-medium text-[var(--rp-text-muted)]" for="sw-e-min">Хв.</label>
                            <input
                                id="sw-e-min"
                                v-model.number="editForm.mute_minutes"
                                type="number"
                                min="1"
                                max="525600"
                                class="rp-input rp-focusable"
                            />
                        </div>
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-primary shrink-0"
                            :disabled="saving"
                            @click="saveEdit"
                        >
                            Зберегти зміни
                        </button>
                    </div>
                </div>
            </template>
        </main>
    </div>
</template>

<script>
import { isStaffRole } from '../lib/userBadgeMenuItems';

const THEME_KEY = 'redpanda-theme';

export default {
    name: 'StaffStopWordsView',
    data() {
        return {
            user: null,
            themeUi: 'system',
            loading: false,
            loadError: '',
            statusMsg: '',
            saving: false,
            rows: [],
            selected: null,
            createForm: {
                word: '',
                category: '',
                match_mode: 'substring',
                action: 'mask',
                mute_minutes: null,
            },
            editForm: {
                word: '',
                category: '',
                match_mode: 'substring',
                action: 'mask',
                mute_minutes: null,
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
        matchLabel(m) {
            return m === 'whole_word' ? 'Ціле слово' : 'Підрядок';
        },
        actionLabel(a) {
            const map = {
                mask: 'Маска',
                reject: 'Відхилити',
                flag: 'Прапорець',
                temp_mute: 'Мут',
            };

            return map[a] || a;
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
            await this.loadList();
        },
        async loadList() {
            this.loading = true;
            this.loadError = '';
            try {
                const { data } = await window.axios.get('/api/v1/mod/filter-words');
                this.rows = Array.isArray(data.data) ? data.data : [];
                this.selected = null;
            } catch (e) {
                this.rows = [];
                this.loadError =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося завантажити список.';
            } finally {
                this.loading = false;
            }
        },
        selectRow(r) {
            this.selected = r;
            this.editForm = {
                word: r.word,
                category: r.category || 'default',
                match_mode: r.match_mode || 'substring',
                action: r.action || 'mask',
                mute_minutes: r.mute_minutes != null ? Number(r.mute_minutes) : null,
            };
            this.statusMsg = '';
        },
        async addRule() {
            if (!this.createForm.word || this.createForm.word.length < 2) {
                this.statusMsg = 'Мінімум 2 символи для слова.';

                return;
            }
            this.saving = true;
            this.statusMsg = '';
            try {
                const body = {
                    word: this.createForm.word,
                    match_mode: this.createForm.match_mode,
                    action: this.createForm.action,
                };
                if (this.createForm.category) {
                    body.category = this.createForm.category;
                }
                if (this.createForm.action === 'temp_mute' && this.createForm.mute_minutes) {
                    body.mute_minutes = this.createForm.mute_minutes;
                }
                await window.axios.post('/api/v1/mod/filter-words', body);
                this.createForm.word = '';
                this.createForm.category = '';
                this.createForm.match_mode = 'substring';
                this.createForm.action = 'mask';
                this.createForm.mute_minutes = null;
                this.statusMsg = 'Правило додано.';
                await this.loadList();
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) || 'Не вдалося додати правило.';
            } finally {
                this.saving = false;
            }
        },
        async saveEdit() {
            if (!this.selected) {
                return;
            }
            this.saving = true;
            this.statusMsg = '';
            try {
                const body = {
                    word: this.editForm.word,
                    category: this.editForm.category || 'default',
                    match_mode: this.editForm.match_mode,
                    action: this.editForm.action,
                };
                if (this.editForm.action === 'temp_mute') {
                    body.mute_minutes = this.editForm.mute_minutes || null;
                }
                const { data } = await window.axios.patch(`/api/v1/mod/filter-words/${this.selected.id}`, body);
                const u = data.data;
                const i = this.rows.findIndex((x) => x.id === u.id);
                if (i >= 0) {
                    this.$set(this.rows, i, u);
                }
                this.selectRow(u);
                this.statusMsg = 'Збережено.';
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) || 'Не вдалося зберегти.';
            } finally {
                this.saving = false;
            }
        },
        async removeRow(r) {
            if (!window.confirm(`Видалити правило «${r.word}»?`)) {
                return;
            }
            this.statusMsg = '';
            try {
                await window.axios.delete(`/api/v1/mod/filter-words/${r.id}`);
                if (this.selected && this.selected.id === r.id) {
                    this.selected = null;
                }
                this.statusMsg = 'Видалено.';
                await this.loadList();
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) || 'Не вдалося видалити.';
            }
        },
    },
};
</script>
