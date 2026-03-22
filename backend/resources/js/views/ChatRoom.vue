<template>
    <div
        class="flex min-h-screen flex-col bg-[var(--rp-bg)] md:h-[100dvh] md:max-h-screen md:flex-row md:overflow-hidden md:p-0"
    >
        <!-- Затемнення (лише мобільний off-canvas) -->
        <button
            v-if="panelOpen && isNarrowViewport"
            type="button"
            class="rp-focusable fixed inset-0 z-40 bg-black/55 md:hidden"
            aria-label="Закрити панель чату"
            @click="closePanel"
        />

        <div
            class="rp-chat-external-wrap min-h-0 min-w-0 max-md:flex max-md:flex-1 max-md:flex-col md:min-h-0 md:flex-1"
        >
            <div
                class="flex min-h-0 min-w-0 flex-1 flex-col bg-[var(--rp-chat-app-bg)] px-3 py-2 md:px-0 md:py-0 md:min-h-0 md:overflow-hidden"
            >
            <ChatRoomHeader
                ref="chatRoomHeader"
                :chat-breadcrumb="chatBreadcrumb"
                :chat-topic-line="chatTopicLine"
                :panel-open="panelOpen"
                :ws-degraded="wsDegraded"
                @toggle-panel="togglePanel"
            />

            <p
                v-if="logoutError"
                class="mb-2 text-sm text-[var(--rp-error)]"
                role="alert"
                aria-live="polite"
            >
                {{ logoutError }}
            </p>

            <main
                id="main-content"
                class="flex min-h-0 w-full flex-1 flex-col gap-3 overflow-hidden pt-0"
                tabindex="-1"
            >
                <div v-if="loadError" class="rp-banner shrink-0" role="alert">
                    {{ loadError }}
                </div>
                <div
                    v-else-if="!loadingRooms && rooms.length === 0"
                    class="rp-banner shrink-0"
                    role="status"
                >
                    Немає доступних кімнат. Зверніться до адміністратора.
                </div>

                <div
                    v-else
                    class="flex min-h-0 flex-1 flex-col overflow-hidden border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-feed-bg)] md:border-0 md:shadow-none"
                >
                    <h2 class="rp-sr-only">Повідомлення</h2>
                    <ChatFeedMessageList
                        ref="chatFeed"
                        :messages="messages"
                        :loading-messages="loadingMessages"
                        :viewer-name="user && user.user_name ? user.user_name : ''"
                        @inline-private="insertFeedInlinePrivatePrefix"
                        @mention="insertFeedReplyPrefix"
                        @edit="startEditMessageFromFeed"
                        @delete="openDeleteMessageConfirm"
                    />

                    <ChatRoomComposer
                        ref="chatComposer"
                        :selected-room-id="selectedRoomId"
                        :sending="sending"
                        :logging-out="loggingOut"
                        :is-guest="Boolean(user && user.guest)"
                        :message-max-length="composerMessageMaxLength"
                        :ensure-sanctum="ensureSanctum"
                        @submit-message="sendMessage"
                        @cycle-edit="onComposerCycleEdit"
                        @logout="logout"
                    />
                </div>
            </main>
        </div>

        <ChatRoomSidebar
            ref="chatRoomSidebar"
            :panel-open="panelOpen"
            :is-narrow-viewport="isNarrowViewport"
            :sidebar-tabs="sidebarTabs"
            :sidebar-tab="sidebarTab"
            :private-list-load-error="privateListLoadError"
            :friends-ignores-load-error="friendsIgnoresLoadError"
            :user="user"
            :badge-menu="badgeMenu"
            :is-badge-menu-open="sidebarBadgeMenuOpen"
            :room-presence-peers="roomPresencePeers"
            :ws-degraded="wsDegraded"
            :peer-lookup-name.sync="peerLookupName"
            :friends-sub-tab.sync="friendsSubTab"
            :friends-accepted="friendsAccepted"
            :friends-accepted-with-menu-peer="friendsAcceptedWithMenuPeer"
            :friends-incoming="friendsIncoming"
            :friends-incoming-with-menu-peer="friendsIncomingWithMenuPeer"
            :friends-outgoing="friendsOutgoing"
            :friends-outgoing-with-menu-peer="friendsOutgoingWithMenuPeer"
            :conversations="conversations"
            :private-conversation-rows="privateConversationRows"
            :rooms="rooms"
            :loading-rooms="loadingRooms"
            :selected-room-id="selectedRoomId"
            :can-create-room="canCreateRoom"
            :chat-settings="chatSettings"
            :creating-room="creatingRoom"
            :create-room-error="createRoomError"
            :room-create-form-key="roomCreateFormKey"
            :ignores="ignores"
            :ignores-with-menu-peer="ignoresWithMenuPeer"
            @close="closePanel"
            @select-tab="selectSidebarTab"
            @open-self-badge-menu="openSelfBadgeMenu"
            @open-peer-badge-menu="openPeerBadgeMenu"
            @sidebar-badge-pick="onSidebarBadgeMenuPick"
            @sidebar-badge-close="closeSidebarBadgeMenu"
            @lookup-private="lookupAndOpenPrivate"
            @open-private-peer="openPrivatePeer"
            @select-room="selectRoom"
            @create-room="onCreateRoom"
            @accept-friend="acceptFriend"
            @reject-friend="rejectFriend"
            @remove-ignore="removeIgnore"
        />

        </div>

        <CommandsHelpModal :open="commandsHelpOpen" @close="commandsHelpOpen = false" />
        <UserInfoModal
            :open="userInfoModalOpen"
            :mode="userInfoModalMode"
            :viewer="user"
            :target="userInfoModalTarget"
            :theme-label="themeLabel"
            @close="closeUserInfoModal"
            @cycle-theme="cycleTheme"
        />
        <ChatSettingsModal
            :open="chatSettingsModalOpen"
            :rooms="rooms"
            :ensure-sanctum="ensureSanctum"
            @close="chatSettingsModalOpen = false"
        />
        <UserProfileModal
            :open="profileModalOpen"
            :user="user"
            :theme-label="themeLabel"
            @close="profileModalOpen = false"
            @updated="onProfileModalUpdated"
            @cycle-theme="cycleTheme"
        />
        <ConfirmDialogModal
            :open="deleteConfirmOpen"
            title="Видалити повідомлення?"
            body="Рядок зникне зі стрічки для всіх у кімнаті. Відновити вміст буде неможливо."
            confirm-label="Видалити"
            cancel-label="Скасувати"
            @close="closeDeleteMessageConfirm"
            @confirm="confirmDeleteMessage"
        />

        <PrivateChatPanel
            v-if="user && privatePeer"
            :peer="privatePeer"
            :messages="privateMessages"
            :loading="loadingPrivateMessages"
            :sending="sendingPrivate"
            :error="privateLoadError"
            :composer-text.sync="privateComposerText"
            :current-user-id="user.id"
            :current-user-name="user.user_name"
            :current-user-avatar-url="user.avatar_url || ''"
            @close="closePrivatePanel"
            @send="sendPrivateMessageFromPanel"
        />
    </div>
</template>

<script>
import ChatFeedMessageList from '../components/chat/ChatFeedMessageList.vue';
import ChatRoomComposer from '../components/chat/ChatRoomComposer.vue';
import ChatRoomHeader from '../components/chat/ChatRoomHeader.vue';
import ChatRoomSidebar from '../components/chat/ChatRoomSidebar.vue';
import ConfirmDialogModal from '../components/ConfirmDialogModal.vue';
import CommandsHelpModal from '../components/CommandsHelpModal.vue';
import PrivateChatPanel from '../components/PrivateChatPanel.vue';
import ChatSettingsModal from '../components/ChatSettingsModal.vue';
import UserProfileModal from '../components/UserProfileModal.vue';
import UserInfoModal from '../components/UserInfoModal.vue';
import { createEcho } from '../lib/echo';
import { normalizePostStyleFromApi } from '../utils/chatMessageStyle';

const THEME_KEY = 'redpanda-theme';
/** Збереження останньої вкладки сайдбару; відсутній/невалідний ключ → «Люди». */
const SIDEBAR_TAB_STORAGE_KEY = 'redpanda-chat-sidebar-tab';
const SIDEBAR_TAB_IDS = ['users', 'friends', 'private', 'rooms', 'ignore'];

function readStoredSidebarTab() {
    if (typeof localStorage === 'undefined') {
        return 'users';
    }
    try {
        const raw = localStorage.getItem(SIDEBAR_TAB_STORAGE_KEY);
        if (raw && SIDEBAR_TAB_IDS.includes(raw)) {
            return raw;
        }
    } catch {
        /* */
    }

    return 'users';
}

const SIDEBAR_TAB_ICONS = {
    users:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>',
    friends:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>',
    private:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>',
    rooms:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
    ignore:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4zm4.5 1c.83 0 1.5-.67 1.5-1.5S17.33 10 16.5 10 15 10.67 15 11.5s.67 1.5 1.5 1.5zM19 3l-4 4 1.5 1.5L20.5 4 19 3z"/></svg>',
};

function peerTargetFromConversationPeerPayload(p) {
    if (!p) {
        return null;
    }

    return {
        id: p.id != null ? Number(p.id) : null,
        user_name: p.user_name != null ? String(p.user_name) : '',
        guest: Boolean(p.guest),
        chat_role: p.chat_role != null ? String(p.chat_role) : 'user',
    };
}

function peerTargetFromFriendUserPayload(u) {
    if (!u) {
        return null;
    }

    return {
        id: Number(u.id),
        user_name: u.user_name,
        guest: false,
        chat_role: 'user',
    };
}

/**
 * Початковий стан бічної панелі (друзі, приват, ігнор).
 * Поля мають збігатися з використанням у шаблоні — не прибирати точково при merge.
 * Прапор echoUserListenerReady додатково скидається в setupEcho при створенні нового Echo (HMR тощо).
 */
function createChatRoomSidebarState() {
    return {
        peerLookupName: '',
        conversations: [],
        friendsAccepted: [],
        friendsIncoming: [],
        friendsOutgoing: [],
        ignores: [],
        privatePeer: null,
        privateMessages: [],
        privateMessageIds: new Set(),
        privateComposerText: '',
        loadingPrivateMessages: false,
        sendingPrivate: false,
        privateLoadError: '',
        echoUserListenerReady: false,
        privateListLoadError: '',
        friendsIgnoresLoadError: '',
    };
}

function normalizePresencePeer(raw) {
    if (!raw || raw.id === undefined || raw.id === null) {
        return null;
    }

    return {
        id: Number(raw.id),
        user_name: raw.user_name != null ? String(raw.user_name) : '',
        guest: Boolean(raw.guest),
        avatar_url: raw.avatar_url != null ? String(raw.avatar_url) : '',
        chat_role: raw.chat_role != null ? String(raw.chat_role) : 'user',
        badge_color: raw.badge_color != null ? String(raw.badge_color) : '',
    };
}

function normalizeMessage(raw) {
    if (!raw || typeof raw.post_id === 'undefined') {
        return null;
    }

    const file = raw.file != null ? Number(raw.file) : 0;
    const image =
        raw.image && raw.image.url
            ? { id: Number(raw.image.id), url: raw.image.url }
            : null;

    const base = {
        post_id: raw.post_id,
        post_roomid: raw.post_roomid,
        user_id: raw.user_id,
        post_date: raw.post_date,
        post_edited_at:
            raw.post_edited_at != null && raw.post_edited_at !== ''
                ? Number(raw.post_edited_at)
                : null,
        post_deleted_at:
            raw.post_deleted_at != null && raw.post_deleted_at !== ''
                ? Number(raw.post_deleted_at)
                : null,
        post_time: raw.post_time,
        post_user: raw.post_user,
        post_message: raw.post_message,
        post_style: normalizePostStyleFromApi(raw.post_style),
        post_color: raw.post_color,
        type: raw.type,
        recipient_user_id:
            raw.recipient_user_id != null && raw.recipient_user_id !== ''
                ? Number(raw.recipient_user_id)
                : null,
        client_message_id: raw.client_message_id,
        avatar: raw.avatar ? String(raw.avatar) : '',
        file,
        image,
    };
    if (Object.prototype.hasOwnProperty.call(raw || {}, 'can_edit')) {
        base.can_edit = Boolean(raw.can_edit);
    }
    if (Object.prototype.hasOwnProperty.call(raw || {}, 'can_delete')) {
        base.can_delete = Boolean(raw.can_delete);
    }

    return base;
}

export default {
    name: 'ChatRoom',
    components: {
        ChatFeedMessageList,
        ChatRoomComposer,
        ChatRoomHeader,
        ChatRoomSidebar,
        ConfirmDialogModal,
        CommandsHelpModal,
        PrivateChatPanel,
        ChatSettingsModal,
        UserProfileModal,
        UserInfoModal,
    },
    data() {
        return {
            user: null,
            rooms: [],
            selectedRoomId: null,
            messages: [],
            messageIds: new Set(),
            loadingRooms: true,
            chatSettings: null,
            creatingRoom: false,
            createRoomError: '',
            roomCreateFormKey: 0,
            loadingMessages: false,
            sending: false,
            loadError: '',
            logoutError: '',
            echo: null,
            echoChannel: null,
            echoSubscribedRoomId: null,
            wsDegraded: false,
            pollTimer: null,
            themeUi: 'system',
            loggingOut: false,
            panelOpen: true,
            sidebarTab: readStoredSidebarTab(),
            friendsSubTab: 'active',
            isNarrowViewport: false,
            mqHandler: null,
            /** Елемент фокусу до відкриття off-canvas (повертаємо при закритті). */
            panelFocusReturnEl: null,
            /** Інші учасники поточної кімнати (Echo presence), без поточного користувача. */
            roomPresencePeers: [],
            ...createChatRoomSidebarState(),
            badgeMenu: null,
            commandsHelpOpen: false,
            userInfoModalOpen: false,
            userInfoModalMode: 'self',
            userInfoModalTarget: null,
            chatSettingsModalOpen: false,
            profileModalOpen: false,
            deleteConfirmOpen: false,
            deleteConfirmTarget: null,
        };
    },
    computed: {
        canCreateRoom() {
            return Boolean(this.user && !this.user.guest && this.user.can_create_room);
        },
        themeLabel() {
            if (this.themeUi === 'light') {
                return 'Тема: світла';
            }
            if (this.themeUi === 'dark') {
                return 'Тема: темна';
            }

            return 'Тема: як у системі';
        },
        currentRoom() {
            return this.rooms.find((r) => r.room_id === this.selectedRoomId) || null;
        },
        chatBreadcrumb() {
            const u = this.user && this.user.user_name;
            const r = this.currentRoom && this.currentRoom.room_name;
            if (u && r) {
                return `${u} › ${r}`;
            }
            if (r) {
                return r;
            }
            if (u) {
                return u;
            }

            return '';
        },
        chatTopicLine() {
            return this.currentRoom && this.currentRoom.topic ? this.currentRoom.topic : '';
        },
        /** Узгоджено з `StoreChatMessageRequest` / `UpdateChatMessageRequest` (T35). */
        composerMessageMaxLength() {
            const u = this.user;
            if (!u || u.guest) {
                return 2000;
            }
            const r = u.chat_role;
            if (r === 'vip' || r === 'moderator' || r === 'admin') {
                return 8000;
            }

            return 4000;
        },
        sidebarTabs() {
            return [
                { id: 'users', title: 'Люди', icon: SIDEBAR_TAB_ICONS.users },
                { id: 'friends', title: 'Друзі', icon: SIDEBAR_TAB_ICONS.friends },
                { id: 'private', title: 'Приват', icon: SIDEBAR_TAB_ICONS.private },
                { id: 'rooms', title: 'Кімнати', icon: SIDEBAR_TAB_ICONS.rooms },
                { id: 'ignore', title: 'Ігнор', icon: SIDEBAR_TAB_ICONS.ignore },
            ];
        },
        privateConversationRows() {
            const list = this.conversations || [];

            return list.map((c, idx) => {
                const key =
                    c && c.peer && c.peer.id != null ? `peer-${c.peer.id}` : `conv-${idx}`;
                const menuPeer =
                    c && c.peer && c.peer.id != null
                        ? peerTargetFromConversationPeerPayload(c.peer)
                        : null;

                return { c, idx, key, menuPeer };
            });
        },
        friendsAcceptedWithMenuPeer() {
            return (this.friendsAccepted || []).map((f) => ({
                ...f,
                menuPeer: peerTargetFromFriendUserPayload(f.user),
            }));
        },
        friendsIncomingWithMenuPeer() {
            return (this.friendsIncoming || []).map((r) => ({
                ...r,
                menuPeer: peerTargetFromFriendUserPayload(r.user),
            }));
        },
        friendsOutgoingWithMenuPeer() {
            return (this.friendsOutgoing || []).map((r) => ({
                ...r,
                menuPeer: peerTargetFromFriendUserPayload(r.user),
            }));
        },
        ignoresWithMenuPeer() {
            return (this.ignores || []).map((row) => ({
                ...row,
                menuPeer: peerTargetFromFriendUserPayload(row.user),
            }));
        },
    },
    watch: {
        panelOpen() {
            this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
        },
        isNarrowViewport() {
            this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
        },
        sidebarTab(to) {
            this.badgeMenu = null;
            try {
                if (typeof localStorage !== 'undefined' && SIDEBAR_TAB_IDS.includes(to)) {
                    localStorage.setItem(SIDEBAR_TAB_STORAGE_KEY, to);
                }
            } catch {
                /* */
            }
            if (to === 'private') {
                this.loadConversations();
            }
            if (to === 'friends' || to === 'ignore') {
                this.loadFriendsAndIgnores();
            }
        },
        badgeMenu(to) {
            document.removeEventListener('mousedown', this.onSidebarBadgeMenuDocMouseDown, true);
            document.removeEventListener('keydown', this.onSidebarBadgeMenuDocKeydown, true);
            if (to) {
                document.addEventListener('mousedown', this.onSidebarBadgeMenuDocMouseDown, true);
                document.addEventListener('keydown', this.onSidebarBadgeMenuDocKeydown, true);
            }
        },
        /** Зміна акаунту / вихід без повного reload: перепідписати приватний канал user.{id}. */
        user(to, from) {
            if (!this.echo) {
                return;
            }
            const prevId = from && from.id;
            const nextId = to && to.id;
            if (prevId != null && nextId != null && Number(prevId) === Number(nextId)) {
                return;
            }
            if (prevId != null) {
                try {
                    this.echo.leave(`user.${prevId}`);
                } catch {
                    /* */
                }
            }
            this.echoUserListenerReady = false;
            if (nextId != null) {
                this.$nextTick(() => this.ensureUserPrivateListener());
            }
        },
    },
    created() {
        this.themeUi = localStorage.getItem(THEME_KEY) || 'system';
    },
    async mounted() {
        document.documentElement.setAttribute('data-theme', this.themeUi);
        this.initViewportListener();
        await this.bootstrap();
        this.$nextTick(() => {
            const c = this.$refs.chatComposer;
            if (c && typeof c.syncComposerInputHeight === 'function') {
                c.syncComposerInputHeight();
            }
        });
        window.addEventListener('keydown', this.onGlobalKeydown);
    },
    beforeDestroy() {
        document.removeEventListener('mousedown', this.onSidebarBadgeMenuDocMouseDown, true);
        document.removeEventListener('keydown', this.onSidebarBadgeMenuDocKeydown, true);
        window.removeEventListener('keydown', this.onGlobalKeydown);
        this.teardownMediaQuery();
        document.body.style.overflow = '';
        this.teardownEcho(true);
        this.stopPoll();
    },
    methods: {
        initViewportListener() {
            if (typeof window === 'undefined' || !window.matchMedia) {
                return;
            }
            const mq = window.matchMedia('(max-width: 767px)');
            this.isNarrowViewport = mq.matches;
            /* T45: панель сайдбару відкрита за замовчуванням і на вузькому viewport (разом із T41 «Люди»). */
            this.panelOpen = true;
            this.mqHandler = () => {
                this.isNarrowViewport = mq.matches;
                if (!mq.matches) {
                    this.panelOpen = true;
                }
                this.$nextTick(() => {
                    this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
                });
            };
            mq.addEventListener('change', this.mqHandler);
            this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
        },
        teardownMediaQuery() {
            if (typeof window === 'undefined' || !window.matchMedia || !this.mqHandler) {
                return;
            }
            const mq = window.matchMedia('(max-width: 767px)');
            mq.removeEventListener('change', this.mqHandler);
            this.mqHandler = null;
        },
        syncBodyScrollLock(lock) {
            document.body.style.overflow = lock ? 'hidden' : '';
        },
        onGlobalKeydown(e) {
            if (e.key !== 'Escape') {
                return;
            }
            if (this.privatePeer) {
                this.closePrivatePanel();

                return;
            }
            if (this.panelOpen && this.isNarrowViewport) {
                this.closePanel();
            }
        },
        focusPanelCloseButton() {
            this.$nextTick(() => {
                const side = this.$refs.chatRoomSidebar;
                const btn =
                    (this.isNarrowViewport && side && side.$refs.panelCloseBtnMobile) ||
                    (side && side.$refs.panelCloseBtnDesktop) ||
                    (side && side.$refs.panelCloseBtnMobile);
                if (btn && typeof btn.focus === 'function') {
                    btn.focus();
                }
            });
        },
        /** Відкрити панель і перевести фокус на кнопку закриття (off-canvas). */
        beginOpeningPanel() {
            if (!this.panelOpen) {
                this.panelFocusReturnEl = document.activeElement;
            }
            this.panelOpen = true;
            if (this.isNarrowViewport) {
                this.focusPanelCloseButton();
            }
        },
        closePanel() {
            if (!this.panelOpen) {
                return;
            }
            const returnEl = this.panelFocusReturnEl;
            this.panelFocusReturnEl = null;
            this.panelOpen = false;
            this.$nextTick(() => {
                if (returnEl && typeof returnEl.focus === 'function') {
                    try {
                        returnEl.focus();
                    } catch {
                        /* */
                    }
                }
            });
        },
        togglePanel() {
            if (this.panelOpen) {
                this.closePanel();
            } else {
                this.beginOpeningPanel();
            }
        },
        selectSidebarTab(id) {
            this.sidebarTab = id;
            this.$nextTick(() => {
                const domId = (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + id;
                const el = document.getElementById(domId);
                if (el && typeof el.focus === 'function') {
                    el.focus();
                }
            });
        },
        cycleTheme() {
            const order = ['system', 'light', 'dark'];
            const i = Math.max(0, order.indexOf(this.themeUi));
            const next = order[(i + 1) % order.length];
            this.themeUi = next;
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem(THEME_KEY, next);
        },
        async logout() {
            this.loggingOut = true;
            this.logoutError = '';
            try {
                await this.ensureSanctum();
                await window.axios.post('/api/v1/auth/logout');
                this.teardownEcho(true);
                this.stopPoll();
                this.user = null;
                await this.$router.replace({ path: '/' });
            } catch {
                this.logoutError = 'Не вдалося вийти. Спробуйте ще раз.';
            } finally {
                this.loggingOut = false;
            }
        },
        async ensureSanctum() {
            await window.axios.get('/sanctum/csrf-cookie');
        },
        async fetchUser() {
            try {
                const { data } = await window.axios.get('/api/v1/auth/user');

                return data.data;
            } catch {
                return null;
            }
        },
        /** Оновлює `user` (зокрема `can_create_room` після нового публічного повідомлення — T44). */
        async refreshAuthUser() {
            const u = await this.fetchUser();
            if (u) {
                this.user = u;
            }
        },
        onProfileModalUpdated(nextUser) {
            if (nextUser) {
                this.user = nextUser;
            }
        },
        async bootstrap() {
            this.user = await this.fetchUser();
            if (!this.user) {
                this.$router.replace({ path: '/' });

                return;
            }

            await Promise.all([this.loadRooms(), this.loadChatSettings()]);
            const qRoom = this.$route.query.room;
            const fromQuery = qRoom ? Number(qRoom) : null;
            if (fromQuery && this.rooms.some((r) => r.room_id === fromQuery)) {
                this.selectedRoomId = fromQuery;
            } else if (this.rooms.length > 0) {
                this.selectedRoomId = this.rooms[0].room_id;
            }

            if (this.selectedRoomId) {
                this.$router.replace({ path: '/chat', query: { room: String(this.selectedRoomId) } }).catch(() => {});
                await this.applyRoomSelection();
            }

            await Promise.all([this.loadConversations(), this.loadFriendsAndIgnores()]);
        },
        async loadChatSettings() {
            try {
                const { data } = await window.axios.get('/api/v1/chat/settings');
                this.chatSettings = data.data || null;
            } catch {
                this.chatSettings = null;
            }
        },
        async onCreateRoom({ room_name, topic }) {
            this.createRoomError = '';
            this.creatingRoom = true;
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.post('/api/v1/rooms', { room_name, topic });
                const created = data.data;
                this.roomCreateFormKey += 1;
                await this.loadRooms();
                if (created && created.room_id) {
                    this.selectedRoomId = created.room_id;
                    await this.$router
                        .replace({ path: '/chat', query: { room: String(created.room_id) } })
                        .catch(() => {});
                    await this.applyRoomSelection();
                }
                const u = await this.fetchUser();
                if (u) {
                    this.user = u;
                }
            } catch (e) {
                const st = e.response && e.response.status;
                const msg =
                    (e.response && e.response.data && e.response.data.message) ||
                    (st === 403 ? 'Немає права створити кімнату.' : null) ||
                    'Не вдалося створити кімнату.';
                this.createRoomError = typeof msg === 'string' ? msg : 'Не вдалося створити кімнату.';
            } finally {
                this.creatingRoom = false;
            }
        },
        async loadRooms() {
            this.loadingRooms = true;
            this.loadError = '';
            try {
                const { data } = await window.axios.get('/api/v1/rooms');
                this.rooms = data.data || [];
            } catch {
                this.loadError = 'Не вдалося завантажити кімнати.';
                this.rooms = [];
            } finally {
                this.loadingRooms = false;
            }
        },
        clearMessages() {
            this.messages = [];
            this.messageIds = new Set();
        },
        inferCanDeleteForMessage(m) {
            return this.inferCanEditForMessage(m);
        },
        inferCanEditForMessage(m) {
            if (!this.user || this.user.guest) {
                return false;
            }
            if (m.post_deleted_at != null && m.post_deleted_at !== '') {
                return false;
            }
            if (m.type !== 'public') {
                return false;
            }
            const role = this.user.chat_role;
            if (role === 'admin') {
                return true;
            }
            if (role === 'moderator') {
                return m.post_color !== 'admin';
            }
            if (Number(m.user_id) !== Number(this.user.id)) {
                return false;
            }
            if (role === 'vip') {
                return true;
            }
            const hours = Number(this.user.message_edit_window_hours);
            const windowSec = (Number.isFinite(hours) && hours > 0 ? hours : 24) * 3600;
            const age = Math.floor(Date.now() / 1000) - Number(m.post_date);

            return age <= windowSec;
        },
        mergeMessage(raw) {
            const m = normalizeMessage(raw);
            if (!m) {
                return;
            }
            if (m.can_edit === undefined) {
                m.can_edit = this.inferCanEditForMessage(m);
            }
            if (m.can_delete === undefined) {
                m.can_delete = this.inferCanDeleteForMessage(m);
            }
            if (m.post_deleted_at != null && m.post_deleted_at !== '') {
                m.post_message = '';
                m.image = null;
                m.file = 0;
                m.can_edit = false;
                m.can_delete = false;
            }
            const rid = this.selectedRoomId;
            if (
                rid != null
                && m.post_roomid != null
                && Number(m.post_roomid) !== Number(rid)
            ) {
                return;
            }
            const existingIdx = this.messages.findIndex((x) => x.post_id === m.post_id);
            if (existingIdx !== -1) {
                const prev = this.messages[existingIdx];
                const next = { ...prev, ...m };
                if (!Object.prototype.hasOwnProperty.call(raw || {}, 'can_edit')) {
                    next.can_edit = prev.can_edit;
                }
                if (!Object.prototype.hasOwnProperty.call(raw || {}, 'can_delete')) {
                    next.can_delete = prev.can_delete;
                }
                if (next.post_deleted_at != null && next.post_deleted_at !== '') {
                    next.post_message = '';
                    next.image = null;
                    next.file = 0;
                    next.can_edit = false;
                    next.can_delete = false;
                }
                this.$set(this.messages, existingIdx, next);
                this.$nextTick(() => this.scrollToBottom());

                return;
            }
            if (this.messageIds.has(m.post_id)) {
                return;
            }
            this.messageIds.add(m.post_id);
            this.messages.push(m);
            this.messages.sort((a, b) => a.post_id - b.post_id);
            this.$nextTick(() => this.scrollToBottom());
        },
        scrollToBottom() {
            const feed = this.$refs.chatFeed;
            if (feed && typeof feed.scrollToBottom === 'function') {
                feed.scrollToBottom();
            }
        },
        async loadMessages() {
            if (!this.selectedRoomId) {
                return;
            }
            this.loadingMessages = true;
            try {
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomId}/messages`,
                    { params: { limit: 80 } },
                );
                this.clearMessages();
                (data.data || []).forEach((row) => this.mergeMessage(row));
            } catch {
                this.loadError = 'Не вдалося завантажити повідомлення.';
            } finally {
                this.loadingMessages = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },
        async pollNewMessages() {
            if (!this.selectedRoomId || !this.wsDegraded) {
                return;
            }
            try {
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomId}/messages`,
                    { params: { limit: 80 } },
                );
                (data.data || []).forEach((row) => this.mergeMessage(row));
            } catch {
                /* ignore */
            }
        },
        startPollIfDegraded() {
            this.stopPoll();
            if (!this.wsDegraded) {
                return;
            }
            this.pollTimer = window.setInterval(() => this.pollNewMessages(), 10000);
        },
        stopPoll() {
            if (this.pollTimer !== null) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        syncPresenceHere(users) {
            const myId = this.user && this.user.id != null ? Number(this.user.id) : null;
            const list = (users || [])
                .map((u) => normalizePresencePeer(u))
                .filter((p) => p && myId !== null && p.id !== myId);
            list.sort((a, b) => a.user_name.localeCompare(b.user_name, 'uk'));
            this.roomPresencePeers = list;
        },
        addPresencePeer(raw) {
            const p = normalizePresencePeer(raw);
            const myId = this.user && this.user.id != null ? Number(this.user.id) : null;
            if (!p || myId === null || p.id === myId) {
                return;
            }
            if (this.roomPresencePeers.some((x) => x.id === p.id)) {
                return;
            }
            const next = [...this.roomPresencePeers, p];
            next.sort((a, b) => a.user_name.localeCompare(b.user_name, 'uk'));
            this.roomPresencePeers = next;
        },
        removePresencePeer(raw) {
            const id = raw && raw.id != null ? Number(raw.id) : null;
            if (id == null) {
                return;
            }
            this.roomPresencePeers = this.roomPresencePeers.filter((x) => x.id !== id);
        },
        teardownEcho(fullDisconnect = false) {
            if (this.echo && this.echoSubscribedRoomId !== null) {
                try {
                    this.echo.leave(`room.${this.echoSubscribedRoomId}`);
                } catch {
                    /* */
                }
            }
            this.echoSubscribedRoomId = null;
            this.echoChannel = null;
            this.roomPresencePeers = [];

            if (fullDisconnect && this.echo) {
                if (this.user) {
                    try {
                        this.echo.leave(`user.${this.user.id}`);
                    } catch {
                        /* */
                    }
                }
                this.echoUserListenerReady = false;
                try {
                    this.echo.disconnect();
                } catch {
                    /* */
                }
                this.echo = null;
            }
        },
        setupEcho() {
            this.teardownEcho(false);

            let echo = this.echo;
            if (!echo) {
                echo = createEcho();
                if (!echo) {
                    this.wsDegraded = true;
                    this.startPollIfDegraded();

                    return;
                }
                this.echo = echo;
                this.echoUserListenerReady = false;
            }

            this.wsDegraded = false;

            const roomId = this.selectedRoomId;
            if (roomId == null) {
                return;
            }

            this.echoSubscribedRoomId = roomId;
            this.roomPresencePeers = [];

            const channel = echo.join(`room.${roomId}`);

            channel.here((users) => {
                this.syncPresenceHere(users);
            });
            channel.joining((u) => {
                this.addPresencePeer(u);
            });
            channel.leaving((u) => {
                this.removePresencePeer(u);
            });

            channel.subscribed(() => {
                this.wsDegraded = false;
                this.stopPoll();
            });

            channel.error(() => {
                this.wsDegraded = true;
                this.roomPresencePeers = [];
                this.startPollIfDegraded();
            });

            channel.listen('.MessagePosted', (payload) => {
                this.mergeMessage(payload);
            });

            channel.listen('.MessageUpdated', (payload) => {
                this.mergeMessage(payload);
            });

            channel.listen('.MessageDeleted', (payload) => {
                this.mergeMessage(payload);
            });

            this.echoChannel = channel;
            this.ensureUserPrivateListener();
        },
        ensureUserPrivateListener() {
            if (!this.echo || !this.user || this.echoUserListenerReady) {
                return;
            }
            this.echoUserListenerReady = true;
            const ch = this.echo.private(`user.${this.user.id}`);
            ch.listen('.PrivateMessagePosted', (payload) => {
                this.onPrivateWsPayload(payload);
            });
            ch.listen('.RoomInlinePrivatePosted', (payload) => {
                this.mergeMessage(payload);
            });
        },
        onPrivateWsPayload(payload) {
            if (!payload || typeof payload.id === 'undefined' || !this.user) {
                return;
            }
            if (Number(payload.recipient_id) !== Number(this.user.id)) {
                return;
            }
            if (
                this.privatePeer
                && Number(payload.sender_id) === Number(this.privatePeer.id)
            ) {
                this.mergePrivateMessage(payload);
                this.privateMessages.sort((a, b) => a.id - b.id);
            }
            this.loadConversations();
        },
        async loadConversations() {
            if (!this.user) {
                return;
            }
            try {
                this.privateListLoadError = '';
                const { data } = await window.axios.get('/api/v1/private/conversations');
                const list = data && data.data;
                this.conversations = Array.isArray(list) ? list : [];
            } catch {
                this.conversations = [];
                this.privateListLoadError = 'Не вдалося завантажити список розмов.';
            }
        },
        async loadFriendsAndIgnores() {
            if (!this.user) {
                return;
            }
            try {
                this.friendsIgnoresLoadError = '';
                const [acc, inc, out, ign] = await Promise.all([
                    window.axios.get('/api/v1/friends'),
                    window.axios.get('/api/v1/friends/requests/incoming'),
                    window.axios.get('/api/v1/friends/requests/outgoing'),
                    window.axios.get('/api/v1/ignores'),
                ]);
                const pickList = (res) => {
                    const d = res && res.data && res.data.data;

                    return Array.isArray(d) ? d : [];
                };
                this.friendsAccepted = pickList(acc);
                this.friendsIncoming = pickList(inc);
                this.friendsOutgoing = pickList(out);
                this.ignores = pickList(ign);
            } catch {
                this.friendsAccepted = [];
                this.friendsIncoming = [];
                this.friendsOutgoing = [];
                this.ignores = [];
                this.friendsIgnoresLoadError = 'Не вдалося завантажити друзів або список ігнору.';
            }
        },
        openPrivatePeer(peer) {
            if (!peer || !peer.id) {
                return;
            }
            this.privatePeer = { id: peer.id, user_name: peer.user_name };
            this.privateLoadError = '';
            this.privateMessages = [];
            this.privateMessageIds = new Set();
            this.sidebarTab = 'private';
            this.loadPrivateMessages();
        },
        closePrivatePanel() {
            this.privatePeer = null;
            this.privateMessages = [];
            this.privateMessageIds = new Set();
            this.privateComposerText = '';
            this.privateLoadError = '';
        },
        async lookupAndOpenPrivate() {
            const name = String(this.peerLookupName || '').trim();
            if (!name) {
                return;
            }
            await this.openPrivateByUserName(name);
            this.peerLookupName = '';
        },
        async openPrivateByUserName(name) {
            if (!name || !this.user) {
                return;
            }
            try {
                const { data } = await window.axios.get('/api/v1/users/lookup', {
                    params: { name },
                });
                this.openPrivatePeer(data.data);
                this.loadError = '';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Користувача не знайдено.';
            }
        },
        sidebarBadgeMenuOpen(rowKey) {
            return Boolean(this.badgeMenu && this.badgeMenu.rowKey === rowKey);
        },
        closeSidebarBadgeMenu() {
            const rf = this.badgeMenu && this.badgeMenu.returnFocusEl;
            this.badgeMenu = null;
            this.$nextTick(() => {
                if (rf && typeof rf.focus === 'function') {
                    try {
                        rf.focus();
                    } catch {
                        /* */
                    }
                }
            });
        },
        onSidebarBadgeMenuDocMouseDown(e) {
            if (!this.badgeMenu) {
                return;
            }
            if (e.target.closest && e.target.closest('[data-rp-user-badge-menu-trigger]')) {
                return;
            }
            if (e.target.closest && e.target.closest('[data-rp-user-badge-inline-menu]')) {
                return;
            }
            this.closeSidebarBadgeMenu();
        },
        onSidebarBadgeMenuDocKeydown(e) {
            if (e.key !== 'Escape' || !this.badgeMenu) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            this.closeSidebarBadgeMenu();
        },
        onSidebarBadgeMenuPick(id) {
            this.onBadgeMenuPick(id);
            this.badgeMenu = null;
        },
        openSelfBadgeMenu(evt, rowKey) {
            if (!this.user || !evt || !evt.currentTarget || !rowKey) {
                return;
            }
            const el = evt.currentTarget;
            if (this.badgeMenu && this.badgeMenu.rowKey === rowKey && this.badgeMenu.returnFocusEl === el) {
                this.badgeMenu = null;

                return;
            }
            this.badgeMenu = {
                mode: 'self',
                target: null,
                rowKey,
                returnFocusEl: el,
            };
        },
        openPeerBadgeMenu(evt, target, rowKey) {
            if (!this.user || !target || !evt || !evt.currentTarget || !rowKey) {
                return;
            }
            const el = evt.currentTarget;
            if (this.badgeMenu && this.badgeMenu.rowKey === rowKey && this.badgeMenu.returnFocusEl === el) {
                this.badgeMenu = null;

                return;
            }
            this.badgeMenu = {
                mode: 'other',
                target: { ...target },
                rowKey,
                returnFocusEl: el,
            };
        },
        insertFeedReplyPrefix(userName) {
            if (!this.user || userName == null || userName === '') {
                return;
            }
            const comp = this.$refs.chatComposer;
            if (!comp || typeof comp.appendToComposer !== 'function') {
                return;
            }
            const nick = String(userName);
            const prefix = `${nick} > `;
            comp.appendToComposer(prefix);
            this.$nextTick(() => comp.focusComposerEnd());
        },
        openDeleteMessageConfirm(message) {
            if (!message || message.post_id == null) {
                return;
            }
            this.deleteConfirmTarget = message;
            this.deleteConfirmOpen = true;
        },
        closeDeleteMessageConfirm() {
            this.deleteConfirmOpen = false;
            this.deleteConfirmTarget = null;
        },
        async confirmDeleteMessage() {
            const m = this.deleteConfirmTarget;
            this.deleteConfirmOpen = false;
            this.deleteConfirmTarget = null;
            if (!m || m.post_id == null || !this.selectedRoomId) {
                return;
            }
            const postId = m.post_id;
            await this.ensureSanctum();
            try {
                const { data } = await window.axios.delete(
                    `/api/v1/rooms/${this.selectedRoomId}/messages/${postId}`,
                );
                if (data.data) {
                    this.mergeMessage(data.data);
                }
                const comp = this.$refs.chatComposer;
                if (comp && typeof comp.clearEditIfPostId === 'function') {
                    comp.clearEditIfPostId(postId);
                }
                this.loadError = '';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося видалити повідомлення.';
            }
        },
        startEditMessageFromFeed(message) {
            const comp = this.$refs.chatComposer;
            if (!comp || typeof comp.loadForEdit !== 'function') {
                return;
            }
            comp.loadForEdit(message);
        },
        onComposerCycleEdit(payload) {
            const comp = this.$refs.chatComposer;
            if (!comp || typeof comp.loadForEdit !== 'function') {
                return;
            }
            const editable = this.messages
                .filter(
                    (m) =>
                        m.type === 'public'
                        && m.can_edit
                        && !(m.post_deleted_at != null && m.post_deleted_at !== ''),
                )
                .sort((a, b) => a.post_id - b.post_id);
            if (editable.length === 0) {
                return;
            }
            if (payload && payload.startLatest) {
                comp.loadForEdit(editable[editable.length - 1]);

                return;
            }
            const delta = payload && payload.delta ? Number(payload.delta) : 0;
            if (!delta) {
                return;
            }
            const cur = typeof comp.getEditPostId === 'function' ? comp.getEditPostId() : null;
            let idx = editable.findIndex((m) => m.post_id === cur);
            if (delta < 0) {
                if (idx === -1) {
                    idx = editable.length;
                }
                idx += delta;
            } else {
                if (idx === -1) {
                    return;
                }
                idx += delta;
            }
            if (idx < 0 || idx >= editable.length) {
                return;
            }
            comp.loadForEdit(editable[idx]);
        },
        insertFeedInlinePrivatePrefix(userName) {
            if (!this.user || userName == null || userName === '') {
                return;
            }
            const nick = String(userName);
            if (nick === this.user.user_name) {
                return;
            }
            const comp = this.$refs.chatComposer;
            if (!comp || typeof comp.appendToComposer !== 'function') {
                return;
            }
            const prefix = `/msg ${nick} `;
            comp.appendToComposer(prefix);
            this.$nextTick(() => comp.focusComposerEnd());
        },
        closeUserInfoModal() {
            this.userInfoModalOpen = false;
            this.userInfoModalTarget = null;
        },
        onBadgeMenuPick(id) {
            const bm = this.badgeMenu;
            if (!bm || !this.user) {
                return;
            }
            if (id === 'info') {
                this.userInfoModalMode = bm.mode;
                this.userInfoModalTarget = bm.mode === 'self' ? null : bm.target;
                this.userInfoModalOpen = true;

                return;
            }
            if (id === 'commands') {
                this.commandsHelpOpen = true;

                return;
            }
            if (id === 'settings') {
                this.chatSettingsModalOpen = true;

                return;
            }
            if (id === 'profile') {
                this.profileModalOpen = true;

                return;
            }
            if (id === 'private') {
                this.openPrivateFromMenuTarget(bm.target);

                return;
            }
            if (id === 'ignore') {
                this.addIgnoreFromMenuTarget(bm.target);

                return;
            }
            if (id === 'friend') {
                this.sendFriendFromMenuTarget(bm.target);

                return;
            }
            if (id === 'mute') {
                this.modMuteFromMenuTarget(bm.target);

                return;
            }
            if (id === 'kick') {
                this.modKickFromMenuTarget(bm.target);

                return;
            }
        },
        openPrivateFromMenuTarget(t) {
            if (!t) {
                return;
            }
            if (t.id != null) {
                this.openPrivatePeer({ id: t.id, user_name: t.user_name });

                return;
            }
            if (t.user_name) {
                this.openPrivateByUserName(t.user_name);
            }
        },
        async addIgnoreFromMenuTarget(t) {
            if (!t || t.id == null) {
                this.loadError =
                    'Потрібен обліковий запис із id (наприклад зі списку онлайн). Спробуйте через нік у полі «Приват за ніком» та вкладку профілю.';

                return;
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/ignores/${t.id}`);
                this.loadError = '';
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося додати до ігнору.';
            }
        },
        async sendFriendFromMenuTarget(t) {
            if (!t || t.id == null) {
                this.loadError = 'Немає id користувача для запиту в друзі.';

                return;
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/friends/${t.id}`);
                this.loadError = '';
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося надіслати запит у друзі.';
            }
        },
        async modMuteFromMenuTarget(t) {
            if (!t || t.id == null) {
                return;
            }
            const raw = window.prompt('Кляп: хвилини (0 — зняти)', '60');
            if (raw === null) {
                return;
            }
            const trimmed = raw.trim();
            let minutes;
            if (trimmed === '') {
                minutes = 0;
            } else {
                minutes = parseInt(trimmed, 10);
                if (Number.isNaN(minutes)) {
                    minutes = 60;
                }
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/mod/users/${t.id}/mute`, { minutes });
                this.loadError = '';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося застосувати кляп.';
            }
        },
        async modKickFromMenuTarget(t) {
            if (!t || t.id == null) {
                return;
            }
            const raw = window.prompt('Вигнання: хвилини (0 — зняти)', '60');
            if (raw === null) {
                return;
            }
            const trimmed = raw.trim();
            let minutes;
            if (trimmed === '') {
                minutes = 0;
            } else {
                minutes = parseInt(trimmed, 10);
                if (Number.isNaN(minutes)) {
                    minutes = 60;
                }
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/mod/users/${t.id}/kick`, { minutes });
                this.loadError = '';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося застосувати вигнання.';
            }
        },
        async loadPrivateMessages() {
            if (!this.privatePeer) {
                return;
            }
            this.loadingPrivateMessages = true;
            this.privateLoadError = '';
            try {
                const { data } = await window.axios.get(
                    `/api/v1/private/peers/${this.privatePeer.id}/messages`,
                    { params: { limit: 80 } },
                );
                this.privateMessageIds = new Set();
                this.privateMessages = [];
                (data.data || []).forEach((row) => this.mergePrivateMessage(row));
                this.privateMessages.sort((a, b) => a.id - b.id);
            } catch (e) {
                this.privateLoadError = e.response?.data?.message || 'Не вдалося завантажити приват.';
            } finally {
                this.loadingPrivateMessages = false;
            }
        },
        mergePrivateMessage(raw) {
            if (!raw || typeof raw.id === 'undefined' || this.privateMessageIds.has(raw.id)) {
                return;
            }
            if (!this.privatePeer || !this.user) {
                return;
            }
            const uid = Number(this.user.id);
            const pid = Number(this.privatePeer.id);
            const sid = Number(raw.sender_id);
            const rid = Number(raw.recipient_id);
            const inThread = (sid === uid && rid === pid) || (sid === pid && rid === uid);
            if (!inThread) {
                return;
            }
            this.privateMessageIds.add(raw.id);
            this.privateMessages.push({
                id: raw.id,
                sender_id: raw.sender_id,
                recipient_id: raw.recipient_id,
                body: raw.body,
                sent_at: raw.sent_at,
                sent_time: raw.sent_time,
                client_message_id: raw.client_message_id,
            });
        },
        async sendPrivateMessageFromPanel(body) {
            if (!this.privatePeer || this.sendingPrivate) {
                return;
            }
            const text = typeof body === 'string' ? body.trim() : '';
            if (!text) {
                return;
            }
            this.sendingPrivate = true;
            await this.ensureSanctum();
            const clientMessageId = crypto.randomUUID();
            try {
                const { data, status } = await window.axios.post(
                    `/api/v1/private/peers/${this.privatePeer.id}/messages`,
                    {
                        message: text,
                        client_message_id: clientMessageId,
                    },
                );
                if (data.data) {
                    this.mergePrivateMessage(data.data);
                    this.privateMessages.sort((a, b) => a.id - b.id);
                }
                if (status === 201 || status === 200) {
                    this.privateComposerText = '';
                }
                await this.loadConversations();
            } catch (e) {
                this.privateLoadError = e.response?.data?.message || 'Не вдалося надіслати.';
            } finally {
                this.sendingPrivate = false;
            }
        },
        async acceptFriend(userId) {
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/friends/${userId}/accept`);
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося прийняти запит.';
            }
        },
        async rejectFriend(userId) {
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/friends/${userId}/reject`);
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося відхилити запит.';
            }
        },
        async removeIgnore(userId) {
            await this.ensureSanctum();
            try {
                await window.axios.delete(`/api/v1/ignores/${userId}`);
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося зняти ігнор.';
            }
        },
        async applyRoomSelection() {
            this.teardownEcho(false);
            this.clearMessages();
            this.loadError = '';
            await this.loadMessages();
            this.setupEcho();
            this.startPollIfDegraded();
        },
        async selectRoom(roomId) {
            if (!roomId || roomId === this.selectedRoomId) {
                return;
            }
            this.selectedRoomId = roomId;
            this.$router.replace({ path: '/chat', query: { room: String(roomId) } }).catch(() => {});
            await this.applyRoomSelection();
            if (this.isNarrowViewport && this.panelOpen) {
                const header = this.$refs.chatRoomHeader;
                const mobileToggle = header && header.$refs.mobilePanelToggle;
                this.panelFocusReturnEl = mobileToggle || this.panelFocusReturnEl;
                this.closePanel();
            }
        },
        async sendMessage() {
            const comp = this.$refs.chatComposer;
            if (!comp || typeof comp.getSendPayload !== 'function') {
                return;
            }
            const { text, imageId, stylePayload, editPostId, editHadFile } = comp.getSendPayload();
            if (!this.selectedRoomId || this.sending) {
                return;
            }
            if (editPostId == null && !text && !imageId) {
                return;
            }
            if (editPostId != null && !text && !editHadFile) {
                return;
            }
            this.sending = true;
            await this.ensureSanctum();
            try {
                if (editPostId != null) {
                    const patchBody = { message: text };
                    patchBody.style = stylePayload || {
                        bold: false,
                        italic: false,
                        underline: false,
                    };
                    const { data, status } = await window.axios.patch(
                        `/api/v1/rooms/${this.selectedRoomId}/messages/${editPostId}`,
                        patchBody,
                    );
                    if (data.data) {
                        this.mergeMessage(data.data);
                    }
                    if (status === 200) {
                        comp.resetAfterSend();
                    }
                } else {
                    const clientMessageId = crypto.randomUUID();
                    const body = {
                        message: text,
                        client_message_id: clientMessageId,
                    };
                    if (imageId) {
                        body.image_id = imageId;
                    }
                    if (stylePayload) {
                        body.style = stylePayload;
                    }
                    const { data, status } = await window.axios.post(
                        `/api/v1/rooms/${this.selectedRoomId}/messages`,
                        body,
                    );
                    if (data.data) {
                        this.mergeMessage(data.data);
                    }
                    if (status === 201 || status === 200) {
                        comp.resetAfterSend();
                        if (data.data && data.data.type === 'public') {
                            await this.refreshAuthUser();
                        }
                    }
                }
            } catch (e) {
                const msg = e.response?.data?.message || 'Не вдалося надіслати.';
                this.loadError = msg;
            } finally {
                this.sending = false;
                this.$nextTick(() => {
                    const c = this.$refs.chatComposer;
                    if (c && typeof c.syncComposerInputHeight === 'function') {
                        c.syncComposerInputHeight();
                    }
                });
            }
        },
    },
};
</script>
