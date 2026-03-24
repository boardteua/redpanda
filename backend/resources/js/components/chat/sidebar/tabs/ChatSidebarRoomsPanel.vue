<template>
    <div
        id="chat-panel-rooms"
        role="tabpanel"
        :aria-labelledby="panelTabLabelledby('rooms')"
        tabindex="-1"
        :aria-hidden="active ? 'false' : 'true'"
    >
        <div v-if="user && !user.guest" class="mb-4">
            <button
                type="button"
                class="rp-focusable w-full rounded-md border border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-tab-active-bg)] px-3 py-2.5 text-sm font-semibold text-[var(--rp-chat-sidebar-fg)] hover:bg-[var(--rp-chat-sidebar-border)]/30"
                @click="$emit('open-add-room')"
            >
                Додати кімнату
            </button>
        </div>
        <div class="mb-3">
            <label class="rp-label" for="chat-panel-rooms-search">Пошук кімнат</label>
            <input
                id="chat-panel-rooms-search"
                v-model="roomSearchInput"
                type="search"
                autocomplete="off"
                class="rp-input rp-focusable w-full"
                placeholder="Назва або опис…"
                aria-label="Швидкий пошук по кімнатах"
                aria-describedby="chat-panel-rooms-search-hint"
            />
            <p id="chat-panel-rooms-search-hint" class="rp-sr-only">
                Фільтрує список за назвою та описом кімнати без перезавантаження сторінки.
            </p>
        </div>
        <p v-if="loadingRooms" class="text-[var(--rp-chat-sidebar-muted)]">Завантаження…</p>
        <p v-else-if="roomSearchNoResults" class="py-6 text-center text-[var(--rp-chat-sidebar-muted)]">
            Немає кімнат за запитом
        </p>
        <ul v-else class="space-y-2">
            <li v-for="r in filteredRooms" :key="r.room_id" class="flex items-stretch gap-1">
                <button
                    type="button"
                    class="rp-focusable rp-chat-side-room-btn min-w-0 flex-1 rounded-md border-2 px-3 py-2 text-left transition-colors"
                    :class="r.room_id === selectedRoomId ? 'is-active' : ''"
                    @click="$emit('select-room', r.room_id)"
                >
                    <span class="block font-semibold text-[var(--rp-chat-sidebar-fg)]">{{ r.room_name }}</span>
                    <span v-if="r.topic" class="mt-0.5 block text-xs text-[var(--rp-chat-sidebar-muted)]">{{
                        r.topic
                    }}</span>
                </button>
                <button
                    v-if="roomListCanManage(r)"
                    type="button"
                    class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center self-center rounded-md border-2 border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-tab-active-bg)] text-[var(--rp-chat-sidebar-fg)] hover:bg-[var(--rp-chat-sidebar-border)]/25"
                    :aria-label="'Редагувати кімнату «' + r.room_name + '»'"
                    @click.stop="$emit('edit-room', r.room_id)"
                >
                    <svg
                        class="h-5 w-5 text-[var(--rp-chat-sidebar-link)]"
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                    >
                        <path
                            d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"
                        />
                    </svg>
                </button>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'ChatSidebarRoomsPanel',
    props: {
        active: { type: Boolean, default: false },
        isNarrowViewport: { type: Boolean, default: false },
        user: { type: Object, default: null },
        rooms: { type: Array, default: () => [] },
        loadingRooms: { type: Boolean, default: false },
        selectedRoomId: {
            default: null,
            validator: (v) => v === null || v === undefined || typeof v === 'number',
        },
    },
    data() {
        return {
            roomSearchInput: '',
            roomSearchDebounced: '',
            roomSearchDebounceTimer: null,
        };
    },
    computed: {
        filteredRooms() {
            const list = this.rooms || [];
            const q = (this.roomSearchDebounced || '').trim().toLowerCase();
            if (!q) {
                return list;
            }

            return list.filter((r) => {
                const name = String(r.room_name || '').toLowerCase();
                const topic = String(r.topic || '').toLowerCase();

                return name.includes(q) || topic.includes(q);
            });
        },
        roomSearchNoResults() {
            if (this.loadingRooms) {
                return false;
            }
            const q = (this.roomSearchDebounced || '').trim();
            const list = this.rooms || [];
            if (!q || list.length === 0) {
                return false;
            }

            return this.filteredRooms.length === 0;
        },
    },
    watch: {
        roomSearchInput(val) {
            if (this.roomSearchDebounceTimer) {
                clearTimeout(this.roomSearchDebounceTimer);
            }
            this.roomSearchDebounceTimer = setTimeout(() => {
                this.roomSearchDebounced = String(val || '').trim();
                this.roomSearchDebounceTimer = null;
            }, 250);
        },
    },
    beforeDestroy() {
        if (this.roomSearchDebounceTimer) {
            clearTimeout(this.roomSearchDebounceTimer);
        }
    },
    methods: {
        panelTabLabelledby(panelKey) {
            return (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + panelKey;
        },
        roomListCanManage(room) {
            const u = this.user;
            if (!u || u.guest || !room) {
                return false;
            }
            const role = u.chat_role;
            if (role === 'moderator' || role === 'admin') {
                return true;
            }
            const cid = room.created_by_user_id;
            return cid != null && Number(cid) === Number(u.id);
        },
    },
};
</script>
