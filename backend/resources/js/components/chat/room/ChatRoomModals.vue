<template>
    <div>
        <CommandsHelpModal
            v-if="user && user.chat_role === 'admin'"
            :open="commandsHelpOpen"
            @close="$emit('commands-help-close')"
        />
        <UserInfoModal
            :open="userInfoModalOpen"
            :mode="userInfoModalMode"
            :viewer="user"
            :target="userInfoModalTarget"
            :room-context-room-id="userInfoRoomId"
            :theme-label="themeLabel"
            @close="$emit('user-info-close')"
            @cycle-theme="$emit('user-info-cycle-theme')"
        />
        <ChatSettingsModal
            :open="chatSettingsModalOpen"
            :rooms="rooms"
            :user="user"
            :ensure-sanctum="ensureSanctum"
            @close="$emit('chat-settings-close')"
            @saved="$emit('chat-settings-saved')"
        />
        <UserProfileModal
            :open="profileModalOpen"
            :user="user"
            :rooms="rooms"
            :conversations="conversations"
            :theme-label="themeLabel"
            @close="$emit('profile-close')"
            @updated="$emit('profile-updated', $event)"
            @cycle-theme="$emit('profile-cycle-theme')"
        />
        <ConfirmDialogModal
            :open="deleteMessageConfirmOpen"
            title="Видалити повідомлення?"
            body="Рядок зникне зі стрічки для всіх у кімнаті. Відновити вміст буде неможливо."
            confirm-label="Видалити"
            cancel-label="Скасувати"
            @close="$emit('delete-message-close')"
            @confirm="$emit('delete-message-confirm')"
        />
        <AddRoomModal
            :open="addRoomModalOpen"
            :user="user"
            :can-create-room="canCreateRoom"
            :chat-settings="chatSettings"
            :creating-room="creatingRoom"
            :form-error="addRoomError"
            @close="$emit('add-room-close')"
            @create-room="$emit('add-room-create', $event)"
        />
        <RoomEditModal
            :open="roomEditModalOpen && !!roomBeingEdited"
            :room="roomBeingEdited"
            :user="user"
            :chat-settings="chatSettings"
            :can-delete-room="roomEditCanDelete"
            :saving-room="roomEditSaving"
            :deleting-room="roomEditDeleting"
            :form-error="editRoomError"
            @close="$emit('room-edit-close')"
            @save-room="$emit('room-edit-save', $event)"
            @request-delete-room="$emit('room-edit-request-delete')"
        />
        <ConfirmDialogModal
            :open="deleteRoomConfirmOpen"
            :z-index="95"
            title="Видалити кімнату?"
            :body="deleteRoomConfirmBody"
            confirm-label="Видалити"
            cancel-label="Скасувати"
            @close="$emit('delete-room-close')"
            @confirm="$emit('delete-room-confirm')"
        />
    </div>
</template>

<script>
import AddRoomModal from './AddRoomModal.vue';
import ChatSettingsModal from '../../ChatSettingsModal.vue';
import CommandsHelpModal from '../../CommandsHelpModal.vue';
import ConfirmDialogModal from '../../ConfirmDialogModal.vue';
import RoomEditModal from './RoomEditModal.vue';
import UserInfoModal from '../../UserInfoModal.vue';
import UserProfileModal from '../../UserProfileModal.vue';

export default {
    name: 'ChatRoomModals',
    components: {
        AddRoomModal,
        ChatSettingsModal,
        CommandsHelpModal,
        ConfirmDialogModal,
        RoomEditModal,
        UserInfoModal,
        UserProfileModal,
    },
    props: {
        commandsHelpOpen: { type: Boolean, default: false },
        userInfoModalOpen: { type: Boolean, default: false },
        userInfoModalMode: { type: String, default: 'self' },
        userInfoModalTarget: { type: Object, default: null },
        userInfoRoomId: { type: [Number, String], default: null },
        user: { type: Object, default: null },
        themeLabel: { type: String, default: '' },
        chatSettingsModalOpen: { type: Boolean, default: false },
        user: { type: Object, default: null },
        rooms: { type: Array, default: () => [] },
        conversations: { type: Array, default: () => [] },
        ensureSanctum: { type: Function, required: true },
        profileModalOpen: { type: Boolean, default: false },
        deleteMessageConfirmOpen: { type: Boolean, default: false },
        addRoomModalOpen: { type: Boolean, default: false },
        canCreateRoom: { type: Boolean, default: false },
        chatSettings: { type: Object, default: null },
        creatingRoom: { type: Boolean, default: false },
        addRoomError: { type: String, default: '' },
        roomEditModalOpen: { type: Boolean, default: false },
        roomBeingEdited: { type: Object, default: null },
        roomEditSaving: { type: Boolean, default: false },
        roomEditDeleting: { type: Boolean, default: false },
        editRoomError: { type: String, default: '' },
        roomEditCanDelete: { type: Boolean, default: false },
        deleteRoomConfirmOpen: { type: Boolean, default: false },
        deleteRoomConfirmBody: { type: String, default: '' },
    },
};
</script>
