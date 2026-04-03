<template>
    <RpModal
        :open="open"
        variant="framed"
        size="lg"
        max-height-class="max-h-[min(90vh,36rem)]"
        :z-index="82"
        :scroll-body="true"
        :aria-busy="busy"
        title="Редагувати кімнату"
        @close="onClose"
    >
        <div v-if="room" class="space-y-4 px-4 py-4">
            <p class="text-sm text-[var(--rp-text-muted)]">
                <span class="font-semibold text-[var(--rp-text)]">{{ room.room_name }}</span>
            </p>
            <p
                v-if="formError"
                role="alert"
                class="rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
            >
                {{ formError }}
            </p>

            <form class="space-y-3" @submit.prevent="submitEdit">
                <div>
                    <label class="rp-label" for="rp-edit-room-name">Назва</label>
                    <input
                        id="rp-edit-room-name"
                        v-model="editName"
                        data-rp-initial-focus
                        type="text"
                        maxlength="191"
                        required
                        class="rp-input rp-focusable w-full text-sm"
                        :disabled="savingRoom || deletingRoom || !canEditDetails"
                        autocomplete="off"
                    />
                </div>
                <div>
                    <label class="rp-label" for="rp-edit-room-topic">Опис</label>
                    <textarea
                        id="rp-edit-room-topic"
                        v-model="editTopic"
                        rows="2"
                        maxlength="2000"
                        class="rp-input rp-focusable w-full resize-y text-sm"
                        :disabled="savingRoom || deletingRoom || !canEditDetails"
                    />
                </div>
                <div v-if="canChangeAccess">
                    <label class="rp-label" for="rp-edit-room-access">Доступ</label>
                    <select
                        id="rp-edit-room-access"
                        v-model.number="editAccess"
                        class="rp-input rp-focusable w-full text-sm"
                        :disabled="savingRoom || deletingRoom"
                    >
                        <option :value="0">Публічна (гості дозволені)</option>
                        <option :value="1">Лише зареєстровані</option>
                        <option :value="2">VIP-зона</option>
                    </select>
                </div>
                <div v-if="showRoomAiBotToggle" class="rounded-md border border-[var(--rp-border-subtle)] p-3">
                    <p class="text-xs text-[var(--rp-text-muted)]">
                        <strong class="text-[var(--rp-text)]">Розумна панда (T184):</strong> дозволити в цій кімнаті відповіді
                        бота, icebreaker та VIP-зображення (узгоджено з глобальним LLM у «Налаштуваннях чату»).
                    </p>
                    <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text)]">
                        <input
                            id="rp-edit-room-ai-bot"
                            v-model="editAiBotEnabled"
                            type="checkbox"
                            class="rp-focusable h-4 w-4 rounded border"
                            :disabled="savingRoom || deletingRoom"
                        />
                        Увімкнути LLM-панду в цій кімнаті
                    </label>
                </div>
                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                    <RpButton
                        native-type="submit"
                        variant="primary"
                        class="text-sm"
                        :loading="savingRoom"
                        :disabled="saveDisabled"
                    >
                        {{ savingRoom ? 'Збереження…' : 'Зберегти' }}
                    </RpButton>
                    <RpButton
                        v-if="canDeleteRoom"
                        variant="ghost"
                        class="text-sm text-[var(--rp-error)]"
                        :disabled="savingRoom || deletingRoom"
                        @click="requestDelete"
                    >
                        Видалити кімнату
                    </RpButton>
                </div>
            </form>
        </div>
    </RpModal>
</template>

<script>
import RpModal from '../../RpModal.vue';

export default {
    name: 'RoomEditModal',
    components: { RpModal },
    props: {
        open: { type: Boolean, default: false },
        room: { type: Object, default: null },
        user: { type: Object, default: null },
        /** Зріз `GET /api/v1/chat/settings` (для `ai_llm_enabled` — той самий джерело, що ChatSettingsModal, T198). */
        chatSettings: { type: Object, default: null },
        /** T199: дозвіл DELETE (батьківський computed за політикою). */
        canDeleteRoom: { type: Boolean, default: false },
        savingRoom: { type: Boolean, default: false },
        deletingRoom: { type: Boolean, default: false },
        formError: { type: String, default: '' },
    },
    data() {
        return {
            editName: '',
            editTopic: '',
            editAccess: 0,
            editAiBotEnabled: true,
        };
    },
    computed: {
        busy() {
            return this.savingRoom || this.deletingRoom;
        },
        isStaff() {
            const r = this.user && this.user.chat_role;
            return r === 'moderator' || r === 'admin';
        },
        canEditDetails() {
            if (!this.user || this.user.guest || !this.room) {
                return false;
            }
            if (this.isStaff) {
                return true;
            }
            const cid = this.room.created_by_user_id;
            return cid != null && Number(cid) === Number(this.user.id);
        },
        canChangeAccess() {
            return this.isStaff && this.room;
        },
        isChatAdmin() {
            const r = this.user && this.user.chat_role;

            return r === 'admin';
        },
        /** Тільки коли глобальний LLM увімкнено — інакше секцію не показуємо (T198). */
        showRoomAiBotToggle() {
            return this.isChatAdmin && Boolean(this.chatSettings && this.chatSettings.ai_llm_enabled);
        },
        /** Є хоча б одне редаговане поле, значення якого відрізняється від поточних даних кімнати. */
        hasUnsavedChanges() {
            const r = this.room;
            if (!r) {
                return false;
            }
            if (this.canEditDetails) {
                const nameOrig = (r.room_name || '').trim();
                if ((this.editName || '').trim() !== nameOrig) {
                    return true;
                }
                const topicOrig = r.topic != null ? String(r.topic).trim() : '';
                if ((this.editTopic || '').trim() !== topicOrig) {
                    return true;
                }
            }
            if (this.canChangeAccess) {
                const accessOrig = Number(r.access) || 0;
                if (Number(this.editAccess) !== accessOrig) {
                    return true;
                }
            }
            if (this.showRoomAiBotToggle) {
                const aiOrig = r.ai_bot_enabled !== false;
                if (Boolean(this.editAiBotEnabled) !== aiOrig) {
                    return true;
                }
            }
            return false;
        },
        saveDisabled() {
            return this.busy || !this.hasUnsavedChanges;
        },
    },
    watch: {
        room: {
            handler(r) {
                this.syncFromRoom(r);
            },
            immediate: true,
        },
        open(v) {
            if (v) {
                this.syncFromRoom(this.room);
            }
        },
    },
    methods: {
        syncFromRoom(r) {
            if (!r) {
                this.editName = '';
                this.editTopic = '';
                this.editAccess = 0;
                this.editAiBotEnabled = true;

                return;
            }
            this.editName = r.room_name || '';
            this.editTopic = r.topic != null ? String(r.topic) : '';
            this.editAccess = Number(r.access) || 0;
            this.editAiBotEnabled = r.ai_bot_enabled !== false;
        },
        onClose() {
            this.$emit('close');
        },
        submitEdit() {
            const r = this.room;
            if (!r || this.saveDisabled) {
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
            if (this.showRoomAiBotToggle) {
                payload.ai_bot_enabled = Boolean(this.editAiBotEnabled);
            }
            const keys = Object.keys(payload).filter((k) => k !== 'room_id');
            if (keys.length === 0) {
                return;
            }
            this.$emit('save-room', payload);
        },
        requestDelete() {
            const r = this.room;
            if (!r || !this.canDeleteRoom) {
                return;
            }
            this.$emit('request-delete-room', r.room_id);
        },
    },
};
</script>
