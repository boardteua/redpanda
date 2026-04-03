<template>
    <div class="flex min-h-screen flex-col bg-[var(--rp-bg)] px-4 py-6 sm:px-6">
        <header class="mx-auto mb-6 flex w-full max-w-5xl flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <router-link
                    :to="{ name: 'chat', query: filterRoomId ? { room: String(filterRoomId) } : {} }"
                    class="rp-focusable shrink-0 text-sm font-medium text-[var(--rp-link)] hover:text-[var(--rp-link-hover)]"
                >
                    ← До чату
                </router-link>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-[var(--rp-text)] sm:text-xl">
                        Архів чату
                    </h1>
                    <p class="mt-0.5 text-sm text-[var(--rp-text-muted)]">
                        Таблиця історії; скористайтесь пошуком для вузької вибірки.
                    </p>
                </div>
            </div>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-5xl flex-1" tabindex="-1">
            <RpBanner v-if="loadError" class="mb-4">
                {{ loadError }}
            </RpBanner>

            <RpPanel class="mb-4 space-y-4">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label class="rp-label" for="archive-per-page">Дописів на сторінку</label>
                        <select
                            id="archive-per-page"
                            v-model.number="perPage"
                            class="rp-input rp-focusable min-w-[5rem]"
                            :disabled="loading"
                            @change="onPerPageChange"
                        >
                            <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                        </select>
                    </div>
                    <div class="min-w-[12rem] flex-1">
                        <label class="rp-label" for="archive-room">Кімната</label>
                        <select
                            id="archive-room"
                            v-model="roomFilter"
                            class="rp-input rp-focusable w-full"
                            :disabled="loading || loadingRooms"
                            @change="onRoomFilterChange"
                        >
                            <option value="">Усі доступні</option>
                            <option v-for="r in rooms" :key="r.room_id" :value="String(r.room_id)">
                                {{ r.room_name }}
                            </option>
                        </select>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <label class="rp-label rp-sr-only" for="archive-search">Пошук</label>
                    <RpTextField
                        id="archive-search"
                        v-model="searchInput"
                        type="search"
                        maxlength="200"
                        class="min-w-[12rem] flex-1"
                        placeholder="Текст або нік…"
                        :disabled="loading"
                        @keyup.enter.native="applySearch"
                    />
                    <RpButton class="shrink-0" :disabled="loading" @click="applySearch">
                        Шукати
                    </RpButton>
                </div>
            </RpPanel>

            <p v-if="loading" class="text-sm text-[var(--rp-text-muted)]" role="status">
                Завантаження…
            </p>

            <div
                v-else
                class="mt-4 overflow-x-auto rounded-md border border-[var(--rp-border-subtle)]"
            >
                <table class="w-full min-w-[28rem] border-collapse text-left text-sm text-[var(--rp-text)]">
                    <thead class="bg-[var(--rp-surface-elevated)] text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                        <tr>
                            <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                Користувач
                            </th>
                            <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                Повідомлення
                            </th>
                            <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2 whitespace-nowrap">
                                Дата
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in rows" :key="m.post_id">
                            <td class="border-b border-[var(--rp-border-subtle)] px-3 py-2 align-top">
                                <div class="flex items-center gap-2">
                                    <UserAvatar
                                        :src="m.avatar || ''"
                                        :name="m.post_user"
                                        variant="table"
                                        decorative
                                    />
                                    <span class="font-medium">{{ m.post_user }}</span>
                                </div>
                            </td>
                            <td
                                class="border-b border-[var(--rp-border-subtle)] px-3 py-2 align-top"
                                :title="m.post_message"
                            >
                                <p
                                    v-if="m.archived_room_name"
                                    class="mb-1 text-xs text-[var(--rp-text-muted)]"
                                >
                                    Кімната видалена: {{ m.archived_room_name }}
                                </p>
                                <img
                                    v-if="m.image && m.image.url"
                                    :src="m.image.url"
                                    alt=""
                                    class="mb-2 max-h-20 max-w-full rounded border border-[var(--rp-border-subtle)] object-contain"
                                    loading="lazy"
                                />
                                <div class="line-clamp-3 max-w-full">
                                    <ChatMessageBody
                                        class="rounded px-0.5"
                                        :body-class="archiveMessageClasses(m.post_style)"
                                        :text="m.post_message"
                                        variant="archive"
                                    />
                                </div>
                            </td>
                            <td class="border-b border-[var(--rp-border-subtle)] px-3 py-2 align-top font-mono text-xs whitespace-nowrap text-[var(--rp-text-muted)]">
                                {{ formatArchiveDate(m) }}
                            </td>
                        </tr>
                        <tr v-if="rows.length === 0">
                            <td colspan="3" class="px-3 py-8 text-center text-[var(--rp-text-muted)]">
                                Немає записів за цими умовами.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="meta && meta.last_page > 0"
                class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-[var(--rp-border-subtle)] pt-4"
                aria-label="Пагінація архіву"
            >
                <p class="text-sm text-[var(--rp-text-muted)]">
                    Сторінка {{ meta.current_page }} з {{ meta.last_page }}
                    <span v-if="meta.total != null" class="text-[var(--rp-text-muted)]"> · Усього {{ meta.total }}</span>
                </p>
                <div class="flex flex-wrap gap-2">
                    <RpButton
                        variant="ghost"
                        class="text-sm"
                        :disabled="loading || meta.current_page <= 1"
                        @click="goPage(1)"
                    >
                        Перша
                    </RpButton>
                    <RpButton
                        variant="ghost"
                        class="text-sm"
                        :disabled="loading || meta.current_page <= 1"
                        @click="goPage(meta.current_page - 1)"
                    >
                        Попередня
                    </RpButton>
                    <RpButton
                        variant="ghost"
                        class="text-sm"
                        :disabled="loading || meta.current_page >= meta.last_page"
                        @click="goPage(meta.current_page + 1)"
                    >
                        Наступна
                    </RpButton>
                    <RpButton
                        variant="ghost"
                        class="text-sm"
                        :disabled="loading || meta.current_page >= meta.last_page"
                        @click="goPage(meta.last_page)"
                    >
                        Остання
                    </RpButton>
                </div>
            </nav>
        </main>
    </div>
</template>

<script>
import ChatMessageBody from '../components/chat/feed/ChatMessageBody.vue';
import { loadChatEmoticonsCatalog } from '../utils/chatEmoticons';
import { chatMessageBodyClassList } from '../utils/chatMessageStyle';
import { formatChatArchiveDateTimeLocal } from '../utils/formatChatMessageTime';

export default {
    name: 'ArchiveChat',
    components: { ChatMessageBody },
    data() {
        return {
            user: null,
            loading: false,
            loadingRooms: false,
            loadError: '',
            rows: [],
            meta: null,
            rooms: [],
            perPageOptions: [10, 25, 50, 100],
            perPage: 25,
            page: 1,
            searchInput: '',
            appliedSearch: '',
            roomFilter: '',
        };
    },
    computed: {
        filterRoomId() {
            const r = this.roomFilter;

            return r === '' ? null : Number(r);
        },
    },
    watch: {
        '$route.query.room'(to) {
            this.syncRoomFromRoute(to);
        },
    },
    async mounted() {
        await this.bootstrap();
    },
    methods: {
        archiveMessageClasses(style) {
            return chatMessageBodyClassList(style);
        },
        syncRoomFromRoute(qRoom) {
            if (qRoom == null || qRoom === '') {
                this.roomFilter = '';

                return;
            }
            const n = Number(qRoom);
            this.roomFilter = Number.isFinite(n) ? String(n) : '';
        },
        formatArchiveDate(m) {
            const formatted = formatChatArchiveDateTimeLocal(m && m.post_date);
            if (formatted) {
                return formatted;
            }

            return m && m.post_time ? String(m.post_time) : '—';
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
            this.syncRoomFromRoute(this.$route.query.room);
            await this.loadRooms();
            await loadChatEmoticonsCatalog();
            await this.loadArchive();
        },
        async loadRooms() {
            this.loadingRooms = true;
            try {
                const { data } = await window.axios.get('/api/v1/rooms');
                this.rooms = Array.isArray(data.data) ? data.data : [];
            } catch {
                this.rooms = [];
            } finally {
                this.loadingRooms = false;
            }
        },
        async loadArchive() {
            this.loading = true;
            this.loadError = '';
            try {
                const params = {
                    page: this.page,
                    per_page: this.perPage,
                };
                if (this.appliedSearch) {
                    params.q = this.appliedSearch;
                }
                if (this.filterRoomId != null) {
                    params.room = this.filterRoomId;
                }
                const { data } = await window.axios.get('/api/v1/archive/messages', { params });
                this.rows = Array.isArray(data.data) ? data.data : [];
                this.meta = data.meta || null;
            } catch {
                this.rows = [];
                this.meta = null;
                this.loadError = 'Не вдалося завантажити архів.';
            } finally {
                this.loading = false;
            }
        },
        applySearch() {
            this.page = 1;
            this.appliedSearch = (this.searchInput || '').trim();
            this.loadArchive();
        },
        onPerPageChange() {
            this.page = 1;
            this.loadArchive();
        },
        onRoomFilterChange() {
            this.page = 1;
            const q = { ...this.$route.query };
            if (this.filterRoomId != null) {
                q.room = String(this.filterRoomId);
            } else {
                delete q.room;
            }
            this.$router.replace({ query: q }).catch(() => {});
            this.loadArchive();
        },
        goPage(p) {
            if (!this.meta || p < 1 || p > this.meta.last_page) {
                return;
            }
            this.page = p;
            this.loadArchive();
        },
    },
};
</script>
