<template>
    <RpModal
        :open="open"
        variant="framed"
        size="lg"
        max-height-class="max-h-[min(90vh,36rem)]"
        :z-index="80"
        :scroll-body="true"
        :aria-busy="busy"
        title="Кімнати"
        @close="onClose"
    >
        <div class="space-y-6 px-4 py-4">
            <p
                v-if="formError"
                role="alert"
                class="rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
            >
                {{ formError }}
            </p>

            <section v-if="canCreateRoom" class="space-y-3 rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-surface-elevated)] p-3">
                <h3 class="text-sm font-semibold text-[var(--rp-text)]">Нова кімната</h3>
                <form class="space-y-2" @submit.prevent="submitCreate">
                    <div>
                        <label class="rp-label" for="rp-modal-new-room-name">Назва</label>
                        <input
                            id="rp-modal-new-room-name"
                            v-model="createName"
                            data-rp-initial-focus
                            type="text"
                            maxlength="191"
                            required
                            class="rp-input rp-focusable w-full text-sm"
                            :disabled="creatingRoom"
                            autocomplete="off"
                        />
                    </div>
                    <div>
                        <label class="rp-label" for="rp-modal-new-room-topic">Опис (необов’язково)</label>
                        <textarea
                            id="rp-modal-new-room-topic"
                            v-model="createTopic"
                            rows="2"
                            maxlength="2000"
                            class="rp-input rp-focusable w-full resize-y text-sm"
                            :disabled="creatingRoom"
                        />
                    </div>
                    <button
                        type="submit"
                        class="rp-focusable w-full rounded-md bg-[var(--rp-chat-sidebar-link)] px-3 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="creatingRoom || !createName.trim()"
                    >
                        {{ creatingRoom ? 'Створення…' : 'Створити кімнату' }}
                    </button>
                </form>
            </section>
            <p
                v-else-if="user && !user.guest && chatSettings"
                class="text-xs leading-relaxed text-[var(--rp-text-muted)]"
            >
                Щоб створювати кімнати, потрібна кількість ваших публічних повідомлень
                <strong>більша</strong> за
                {{ chatSettings.room_create_min_public_messages }}
                (правила на сервері; див. налаштування чату).
            </p>

            <section v-if="editableRooms.length > 0" class="space-y-3">
                <h3 class="text-sm font-semibold text-[var(--rp-text)]">Редагування</h3>
                <div>
                    <label class="rp-label" for="rp-modal-edit-room-pick">Кімната</label>
                    <select
                        id="rp-modal-edit-room-pick"
                        v-model.number="editRoomId"
                        class="rp-input rp-focusable w-full text-sm"
                        :disabled="savingRoom || deletingRoom"
                        @change="syncEditFormFromSelection"
                    >
                        <option v-for="r in editableRooms" :key="r.room_id" :value="r.room_id">
                            {{ r.room_name }}
                        </option>
                    </select>
                </div>
                <form class="space-y-2" @submit.prevent="submitEdit">
                    <div>
                        <label class="rp-label" for="rp-modal-edit-room-name">Назва</label>
                        <input
                            id="rp-modal-edit-room-name"
                            v-model="editName"
                            type="text"
                            maxlength="191"
                            required
                            class="rp-input rp-focusable w-full text-sm"
                            :disabled="savingRoom || deletingRoom || !canEditDetails"
                            autocomplete="off"
                        />
                    </div>
                    <div>
                        <label class="rp-label" for="rp-modal-edit-room-topic">Опис</label>
                        <textarea
                            id="rp-modal-edit-room-topic"
                            v-model="editTopic"
                            rows="2"
                            maxlength="2000"
                            class="rp-input rp-focusable w-full resize-y text-sm"
                            :disabled="savingRoom || deletingRoom || !canEditDetails"
                        />
                    </div>
                    <div v-if="canChangeAccess">
                        <label class="rp-label" for="rp-modal-edit-room-access">Доступ</label>
                        <select
                            id="rp-modal-edit-room-access"
                            v-model.number="editAccess"
                            class="rp-input rp-focusable w-full text-sm"
                            :disabled="savingRoom || deletingRoom"
                        >
                            <option :value="0">Публічна (гості дозволені)</option>
                            <option :value="1">Лише зареєстровані</option>
                            <option :value="2">VIP-зона</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                        <button
                            type="submit"
                            class="rp-focusable rounded-md bg-[var(--rp-chat-sidebar-link)] px-3 py-2 text-sm font-semibold text-white hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="savingRoom || deletingRoom || !editRoom"
                        >
                            {{ savingRoom ? 'Збереження…' : 'Зберегти' }}
                        </button>
                        <button
                            v-if="canDeleteSelected"
                            type="button"
                            class="rp-focusable rounded-md border border-[var(--rp-error)] px-3 py-2 text-sm font-semibold text-[var(--rp-error)] hover:bg-[var(--rp-error-bg)] disabled:cursor-not-allowed disabled:opacity-50"
                            :disabled="savingRoom || deletingRoom"
                            @click="requestDelete"
                        >
                            Видалити кімнату
                        </button>
                    </div>
                    <p v-if="editRoom && !canDeleteSelected && messagesCount > 0" class="text-xs text-[var(--rp-text-muted)]">
                        Видалення недоступне: у кімнаті вже є повідомлення в історії.
                    </p>
                </form>
            </section>
            <p v-else-if="user && !user.guest" class="text-sm text-[var(--rp-text-muted)]">
                Немає кімнат, які ви можете редагувати.
            </p>
        </div>
    </RpModal>
</template>

<script>
import RpModal from '../RpModal.vue';

export default {
    name: 'RoomManageModal',
    components: { RpModal },
    props: {
        open: { type: Boolean, default: false },
        user: { type: Object, default: null },
        rooms: { type: Array, default: () => [] },
        canCreateRoom: { type: Boolean, default: false },
        chatSettings: { type: Object, default: null },
        selectedRoomId: {
            default: null,
            validator: (v) => v === null || v === undefined || typeof v === 'number',
        },
        creatingRoom: { type: Boolean, default: false },
        savingRoom: { type: Boolean, default: false },
        deletingRoom: { type: Boolean, default: false },
        formError: { type: String, default: '' },
    },
    data() {
        return {
            createName: '',
            createTopic: '',
            editRoomId: null,
            editName: '',
            editTopic: '',
            editAccess: 0,
        };
    },
    computed: {
        busy() {
            return this.creatingRoom || this.savingRoom || this.deletingRoom;
        },
        isStaff() {
            const r = this.user && this.user.chat_role;
            return r === 'moderator' || r === 'admin';
        },
        editableRooms() {
            const u = this.user;
            if (!u || u.guest) {
                return [];
            }
            return (this.rooms || []).filter((r) => this.canManageRoom(r));
        },
        editRoom() {
            if (this.editRoomId == null) {
                return null;
            }
            return this.editableRooms.find((x) => x.room_id === this.editRoomId) || null;
        },
        canEditDetails() {
            if (!this.user || this.user.guest || !this.editRoom) {
                return false;
            }
            if (this.isStaff) {
                return true;
            }
            const cid = this.editRoom.created_by_user_id;
            return cid != null && Number(cid) === Number(this.user.id);
        },
        canChangeAccess() {
            return this.isStaff && this.editRoom;
        },
        messagesCount() {
            if (!this.editRoom || this.editRoom.messages_count == null) {
                return 0;
            }
            return Number(this.editRoom.messages_count);
        },
        canDeleteSelected() {
            return this.editRoom && this.canManageRoom(this.editRoom) && this.messagesCount === 0;
        },
    },
    watch: {
        open(v) {
            if (v) {
                this.resetCreateFields();
                this.pickInitialEditRoom();
                this.syncEditFormFromSelection();
            }
        },
        editableRooms() {
            if (this.open) {
                this.ensureValidEditSelection();
            }
        },
    },
    methods: {
        canManageRoom(r) {
            if (!this.user || this.user.guest) {
                return false;
            }
            if (this.isStaff) {
                return true;
            }
            const cid = r.created_by_user_id;
            return cid != null && Number(cid) === Number(this.user.id);
        },
        resetCreateFields() {
            this.createName = '';
            this.createTopic = '';
        },
        pickInitialEditRoom() {
            const list = this.editableRooms;
            if (list.length === 0) {
                this.editRoomId = null;

                return;
            }
            const sel = this.selectedRoomId;
            if (sel != null && list.some((r) => r.room_id === sel)) {
                this.editRoomId = sel;

                return;
            }
            this.editRoomId = list[0].room_id;
        },
        ensureValidEditSelection() {
            const list = this.editableRooms;
            if (list.length === 0) {
                this.editRoomId = null;

                return;
            }
            if (!list.some((r) => r.room_id === this.editRoomId)) {
                this.pickInitialEditRoom();
                this.syncEditFormFromSelection();
            }
        },
        syncEditFormFromSelection() {
            const r = this.editRoom;
            if (!r) {
                this.editName = '';
                this.editTopic = '';
                this.editAccess = 0;

                return;
            }
            this.editName = r.room_name || '';
            this.editTopic = r.topic != null ? String(r.topic) : '';
            this.editAccess = Number(r.access) || 0;
        },
        onClose() {
            this.$emit('close');
        },
        submitCreate() {
            const name = (this.createName || '').trim();
            if (!name) {
                return;
            }
            const topic = (this.createTopic || '').trim();
            this.$emit('create-room', { room_name: name, topic: topic || null });
        },
        submitEdit() {
            const r = this.editRoom;
            if (!r) {
                return;
            }
            const payload = { room_id: r.room_id };
            if (this.canEditDetails) {
                payload.room_name = (this.editName || '').trim();
                payload.topic = (this.editTopic || '').trim() || null;
            }
            if (this.canChangeAccess) {
                payload.access = this.editAccess;
            }
            const keys = Object.keys(payload).filter((k) => k !== 'room_id');
            if (keys.length === 0) {
                return;
            }
            this.$emit('save-room', payload);
        },
        requestDelete() {
            const r = this.editRoom;
            if (!r || !this.canDeleteSelected) {
                return;
            }
            this.$emit('request-delete-room', r.room_id);
        },
    },
};
</script>
