<template>
    <div
        class="flex h-full min-h-0 w-full min-w-0 flex-1 flex-col overflow-hidden bg-[var(--rp-bg)] md:flex-row md:p-0"
    >
        <button
            v-if="panelOpen && isNarrowViewport"
            type="button"
            class="rp-focusable fixed inset-0 z-40 bg-black/55 md:hidden"
            aria-label="Закрити панель чату"
            @click="closePanel"
        />

        <div
            class="rp-chat-external-wrap min-h-0 min-w-0 max-md:flex max-md:min-h-0 max-md:flex-1 max-md:flex-col max-md:overflow-hidden md:min-h-0 md:flex-1"
        >
            <ChatRoomMainColumn
                ref="chatMainColumn"
                :panel-open="panelOpen"
                :is-narrow-viewport="isNarrowViewport"
                :private-unread-total="totalPrivateUnread"
                :chat-title="chatTitle"
                :chat-topic-line="chatTopicLine"
                :ws-degraded="wsDegraded"
                :logout-error="logoutError"
                :load-error="loadError"
                :rooms-empty="!loadingRooms && rooms.length === 0"
                @toggle-panel="togglePanel"
            >
                <h2 class="rp-sr-only">Повідомлення</h2>
                <ChatFeedMessageList
                    ref="chatFeed"
                    :messages="messages"
                    :loading-messages="loadingMessages"
                    :viewer-name="user && user.user_name ? user.user_name : ''"
                    :current-room-id="selectedRoomId != null ? Number(selectedRoomId) : null"
                    :divider-before-post-id="newMsgDividerBeforePostId"
                    :divider-dismissed="newMsgDividerDismissed"
                    :bottom-dismiss-suppress-until="roomReadSuppressBottomUntil"
                    :sync-key="feedSyncKey"
                    @inline-private="insertFeedInlinePrivatePrefix"
                    @mention="insertFeedReplyPrefix"
                    @go-to-room="selectRoom"
                    @edit="startEditMessageFromFeed"
                    @delete="openDeleteMessageConfirm"
                    @feed-top-visible="onFeedTopVisible"
                    @feed-bottom-visible="onFeedBottomVisible"
                />

                <ChatRoomComposer
                    ref="chatComposer"
                    :selected-room-id="selectedRoomId"
                    :sending="sending"
                    :logging-out="loggingOut"
                    :is-guest="Boolean(user && user.guest)"
                    :chat-upload-disabled="Boolean(user && !user.guest && user.chat_upload_disabled)"
                    :max-chat-image-upload-bytes="maxChatImageUploadBytesFromSettings"
                    :message-max-length="composerMessageMaxLength"
                    :ensure-sanctum="ensureSanctum"
                    @submit-message="sendMessage"
                    @cycle-edit="onComposerCycleEdit"
                    @logout="logout"
                />
            </ChatRoomMainColumn>

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
                :peer-presence-status-by-user-id="peerPresenceStatusByUserId"
                :peer-presence-status-fetch-loading="peerPresenceStatusFetchLoading"
                :peer-sex-hints-by-user-id="peerSexHintsByUserId"
                :viewer-presence-status="viewerPresenceStatus"
                :ws-degraded="wsDegraded"
                :peer-lookup-name.sync="peerLookupName"
                :peer-autocomplete-suggestions="peerAutocompleteSuggestions"
                :peer-autocomplete-highlight-index="peerAutocompleteHighlightIndex"
                :peer-autocomplete-loading="peerAutocompleteLoading"
                :peer-autocomplete-open="peerAutocompletePanelOpen"
                :friends-sub-tab.sync="friendsSubTab"
                :friends-accepted="friendsAccepted"
                :friends-accepted-with-menu-peer="friendsAcceptedWithMenuPeer"
                :friends-incoming="friendsIncoming"
                :friends-incoming-with-menu-peer="friendsIncomingWithMenuPeer"
                :friends-outgoing="friendsOutgoing"
                :friends-outgoing-with-menu-peer="friendsOutgoingWithMenuPeer"
                :conversations="conversations"
                :private-conversation-rows="privateConversationRows"
                :private-unread-total="totalPrivateUnread"
                :rooms="rooms"
                :loading-rooms="loadingRooms"
                :selected-room-id="selectedRoomId"
                :ignores="ignores"
                :ignores-with-menu-peer="ignoresWithMenuPeer"
                @close="closePanel"
                @select-tab="selectSidebarTab"
                @open-self-badge-menu="openSelfBadgeMenu"
                @open-peer-badge-menu="openPeerBadgeMenu"
                @sidebar-badge-pick="onSidebarBadgeMenuPick"
                @sidebar-badge-close="closeSidebarBadgeMenu"
                @lookup-private="lookupAndOpenPrivate"
                @peer-lookup-keydown="onPeerLookupKeydown"
                @pick-peer-autocomplete="pickPeerAutocomplete"
                @open-private-peer="openPrivatePeer"
                @select-room="selectRoom"
                @open-add-room="openAddRoomModal"
                @edit-room="openRoomEditor"
                @accept-friend="acceptFriend"
                @reject-friend="rejectFriend"
                @remove-ignore="removeIgnore"
            />
        </div>

        <ChatRoomModals
            :commands-help-open="commandsHelpOpen"
            :user-info-modal-open="userInfoModalOpen"
            :user-info-modal-mode="userInfoModalMode"
            :user-info-modal-target="userInfoModalTarget"
            :user-info-room-id="selectedRoomId"
            :user="user"
            :theme-label="themeLabel"
            :chat-settings-modal-open="chatSettingsModalOpen"
            :rooms="rooms"
            :conversations="conversations"
            :ensure-sanctum="ensureSanctum"
            :profile-modal-open="profileModalOpen"
            :delete-message-confirm-open="deleteConfirmOpen"
            :add-room-modal-open="addRoomModalOpen"
            :can-create-room="canCreateRoom"
            :chat-settings="chatSettings"
            :creating-room="creatingRoom"
            :add-room-error="addRoomError"
            :room-edit-modal-open="roomEditModalOpen"
            :room-being-edited="roomBeingEdited"
            :room-edit-saving="roomEditSaving"
            :room-edit-deleting="roomEditDeleting"
            :edit-room-error="editRoomError"
            :room-edit-can-delete="roomEditCanDelete"
            :delete-room-confirm-open="deleteRoomConfirmOpen"
            :delete-room-confirm-body="deleteRoomConfirmBody"
            @commands-help-close="commandsHelpOpen = false"
            @user-info-close="closeUserInfoModal"
            @user-info-cycle-theme="cycleTheme"
            @chat-settings-close="onChatSettingsModalClose"
            @chat-settings-saved="onChatSettingsModalSaved"
            @profile-close="profileModalOpen = false"
            @profile-updated="onProfileModalUpdated"
            @profile-cycle-theme="cycleTheme"
            @delete-message-close="closeDeleteMessageConfirm"
            @delete-message-confirm="confirmDeleteMessage"
            @add-room-close="addRoomModalOpen = false"
            @add-room-create="onAddRoomModalCreate"
            @room-edit-close="closeRoomEditModal"
            @room-edit-save="onRoomEditModalSave"
            @room-edit-request-delete="onRoomEditRequestDelete"
            @delete-room-close="closeDeleteRoomConfirm"
            @delete-room-confirm="confirmDeleteRoom"
        />

        <PrivateChatPanel
            v-if="user && privatePeer"
            ref="privateChatPanel"
            :peer="privatePeer"
            :messages="privateMessages"
            :loading="loadingPrivateMessages"
            :sending="sendingPrivate"
            :error="privateLoadError"
            :composer-text.sync="privateComposerText"
            :current-user-id="user.id"
            :current-user-name="user.user_name"
            :current-user-avatar-url="user.avatar_url || ''"
            :show-slash-docs="user.chat_role === 'admin'"
            :is-guest="Boolean(user.guest)"
            :chat-upload-disabled="Boolean(!user.guest && user.chat_upload_disabled)"
            :max-chat-image-upload-bytes="maxChatImageUploadBytesFromSettings"
            :message-max-length="composerMessageMaxLength"
            :ensure-sanctum="ensureSanctum"
            @close="closePrivatePanel"
            @send="sendPrivateMessageFromPanel"
            @top-visible="onPrivateTopVisible"
        />
        <RpWebPushPrompt :user="user" :ensure-sanctum="ensureSanctum" />
    </div>
</template>

<script>
import ChatFeedMessageList from '../components/chat/feed/ChatFeedMessageList.vue';
import ChatRoomComposer from '../components/chat/composer/ChatRoomComposer.vue';
import ChatRoomMainColumn from '../components/chat/room/ChatRoomMainColumn.vue';
import ChatRoomModals from '../components/chat/room/ChatRoomModals.vue';
import ChatRoomSidebar from '../components/chat/sidebar/ChatRoomSidebar.vue';
import PrivateChatPanel from '../components/PrivateChatPanel.vue';
import RpWebPushPrompt from '../components/RpWebPushPrompt.vue';
import { createEcho } from '../lib/echo';
import {
    ensureAuth0BootstrapFromLandingApi,
    getAuth0AccessTokenSilentlyOrNull,
    logoutAuth0IfLoggedIn,
} from '../lib/rpAuth0';
import { loadChatEmoticonsCatalog } from '../utils/chatEmoticons';
import {
    markChatSoundUserActivated,
    maybePlayGlobalGsound,
    playActiveRoomIncomingSounds,
} from '../utils/chatNotificationSounds';
import { resetFaviconPrivateUnreadBadge, setFaviconPrivateUnreadBadge } from '../utils/faviconUnreadBadge';
import { showError } from '../utils/rpToastStack';
import { buildChatRoomBrowserTitle } from '../utils/chatDocumentTitle';
import {
    PEER_PRESENCE_JOIN_DEBOUNCE_MS,
    PRESENCE_AWAY_IDLE_SEC,
    PRESENCE_INACTIVE_IDLE_SEC,
    getResolvedTheme,
    readStoredSidebarTab,
    SIDEBAR_TAB_ICONS,
    SIDEBAR_TAB_IDS,
    SIDEBAR_TAB_STORAGE_KEY,
    THEME_KEY,
} from '../chat/chatRoomConstants';
import {
    normalizeMessage,
    normalizePresencePeer,
    peerTargetFromConversationPeerPayload,
    peerTargetFromFriendUserPayload,
} from '../chat/chatRoomNormalizers';
import { createChatRoomSidebarState } from '../chat/chatRoomSidebarState';
import { chatRoomFriendsIgnoresMethods } from '../chat/chatRoomFriendsIgnoresMethods';
import { chatRoomPeerAutocompleteMethods } from '../chat/chatRoomPeerAutocompleteMethods';
import { chatRoomPrivateMethods } from '../chat/chatRoomPrivateMethods';
import {
    apiRoomPathSegment,
    buildChatRoute,
    CHAT_DEFAULT_PUBLIC_ROOM_ID,
    isChatRoute,
    staffContextQuery,
} from '../utils/chatRoomNavigation';
import { postWithOneNetworkRetry } from '../utils/requestRetry';
import { hasXsrfTokenCookie } from '../utils/sanctumCsrf';
import { logRudaPandaLlmDebugFromApiResponse } from '../dev/rudaPandaLlmConsole';

export default {
    name: 'ChatRoom',
    components: {
        ChatFeedMessageList,
        ChatRoomComposer,
        ChatRoomMainColumn,
        ChatRoomModals,
        ChatRoomSidebar,
        PrivateChatPanel,
        RpWebPushPrompt,
    },
    data() {
        return {
            user: null,
            rooms: [],
            selectedRoomId: null,
            messages: [],
            messageIds: new Set(),
            olderRoomCursor: null,
            olderRoomHasMore: true,
            loadingOlderRoom: false,
            loadingRooms: true,
            chatSettings: null,
            creatingRoom: false,
            addRoomModalOpen: false,
            addRoomError: '',
            roomEditModalOpen: false,
            roomEditRoomId: null,
            editRoomError: '',
            roomEditSaving: false,
            roomEditDeleting: false,
            deleteRoomConfirmOpen: false,
            pendingDeleteRoomId: null,
            loadingMessages: false,
            /** Після першого bootstrap — щоб `$route.query` watcher не зрізав `focus_post` до завантаження. */
            chatBootstrapDone: false,
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
            /** userId (string) → online | away | inactive (T48). */
            peerPresenceStatusByUserId: {},
            /** T126: true між оновленням списку пірів і завершенням fetch presence-statuses. */
            peerPresenceStatusFetchLoading: false,
            /** T126: лічильник для ігнорування застарілого finally після debounce/нового піра. */
            peerPresenceStatusFetchEpoch: 0,
            /** T49: userId → { sex } з peer-hints (для зареєстрованого переглядача). */
            peerSexHintsByUserId: {},
            presenceLastActivityAt: 0,
            documentHiddenFlag: false,
            presenceLastSentStatus: null,
            presenceLastPostedAt: null,
            presenceTickTimer: null,
            presenceActivityDebounceTimer: null,
            presenceFetchDebounceTimer: null,
            onPresenceVisibilityBound: null,
            onPresenceUserActivityBound: null,
            presenceUserActivityListenerOpts: null,
            ...createChatRoomSidebarState(),
            olderPrivateCursor: null,
            olderPrivateHasMore: true,
            loadingOlderPrivate: false,
            badgeMenu: null,
            commandsHelpOpen: false,
            userInfoModalOpen: false,
            userInfoModalMode: 'self',
            userInfoModalTarget: null,
            chatSettingsModalOpen: false,
            profileModalOpen: false,
            deleteConfirmOpen: false,
            deleteConfirmTarget: null,
            /** T47: перший post_id блоку «нові» після заходу в кімнату (не зміщується від WS). */
            newMsgDividerBeforePostId: null,
            newMsgDividerDismissed: false,
            roomReadSuppressBottomUntil: 0,
            markReadDebounceTimer: null,
            /** T162: тимчасові негативні post_id для optimistic UI у стрічці кімнати. */
            optimisticPostSeq: 0,
            /** T162: тимчасові негативні id для optimistic у приватній панелі. */
            privateOptimisticSeq: 0,
            /** Лічильник для ігнорування застарілих async-відповідей loadPrivateMessages. */
            privateMessagesLoadEpoch: 0,
            /** T170: debounce REST-синхронізації стрічки після повернення з фону / push. */
            feedResumeSyncTimer: null,
        };
    },
    computed: {
        feedSyncKey() {
            const n = this.messages.length;
            const last = n ? this.messages[n - 1].post_id : 0;

            return `${n}:${last}`;
        },
        roomHistoryChunkSize() {
            const n = Number(this.user?.chat_history_prefs?.room_history_chunk_size);

            return Number.isFinite(n) ? Math.min(100, Math.max(1, Math.floor(n))) : 20;
        },
        privateHistoryChunkSize() {
            const n = Number(this.user?.chat_history_prefs?.private_history_chunk_size);

            return Number.isFinite(n) ? Math.min(100, Math.max(1, Math.floor(n))) : 5;
        },
        canCreateRoom() {
            return Boolean(this.user && !this.user.guest && this.user.can_create_room);
        },
        roomBeingEdited() {
            if (this.roomEditRoomId == null) {
                return null;
            }
            return this.rooms.find((r) => r.room_id === this.roomEditRoomId) || null;
        },
        /** T199: матриця DELETE узгоджена з RoomPolicy::delete. */
        roomEditCanDelete() {
            const r = this.roomBeingEdited;
            const u = this.user;
            if (!r || !u || u.guest) {
                return false;
            }
            const role = u.chat_role;
            if (role === 'admin') {
                return true;
            }
            if (role === 'moderator') {
                if (r.created_by_user_id == null) {
                    return true;
                }

                return !r.creator_is_chat_admin;
            }
            if (Number(r.created_by_user_id) === Number(u.id) && this.canCreateRoom) {
                return true;
            }

            return false;
        },
        deleteRoomConfirmBody() {
            const r = this.roomBeingEdited;
            const n = r && r.messages_count != null ? Number(r.messages_count) : 0;
            if (n > 0) {
                return 'Кімнату буде видалено зі списку. Дописи зникнуть зі стрічки; у архіві вони лишаться з міткою видаленої кімнати (за вашим доступом).';
            }

            return 'Кімната зникне зі списку. У ній ще немає повідомлень у стрічці.';
        },
        /** Реактивний статус «я» для індикатора в сайдбарі (T48). */
        viewerPresenceStatus() {
            void this.presenceLastActivityAt;
            void this.documentHiddenFlag;

            return this.computeLocalPresenceStatus();
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
        /** T153: сегмент для `…/rooms/{segment}/…` (slug або numeric id). */
        selectedRoomApiSegment() {
            return apiRoomPathSegment(this.rooms || [], this.selectedRoomId);
        },
        chatTitle() {
            const r = this.currentRoom && this.currentRoom.room_name;

            return r ? String(r) : '';
        },
        chatTopicLine() {
            return this.currentRoom && this.currentRoom.topic ? this.currentRoom.topic : '';
        },
        /** T93 — заголовок вкладки браузера на маршруті `/chat`. */
        browserDocumentTitleForChat() {
            return buildChatRoomBrowserTitle({
                loadError: Boolean(this.loadError),
                loadingRooms: this.loadingRooms,
                roomsLength: (this.rooms || []).length,
                selectedRoomId: this.selectedRoomId,
                roomName: this.currentRoom && this.currentRoom.room_name,
            });
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
        /** T86: фактичний ліміт байтів для `POST /api/v1/images` (мінімум з адмін-налаштування та PHP). */
        maxChatImageUploadBytesFromSettings() {
            const cs = this.chatSettings;
            if (!cs || cs.max_chat_image_upload_bytes == null) {
                return null;
            }
            const n = Number(cs.max_chat_image_upload_bytes);

            return Number.isFinite(n) && n > 0 ? n : null;
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
        /** T196: локальний список ігнору для WS / merge (паралельно до фільтра в API). */
        ignoredUserIdsSet() {
            const s = new Set();
            for (const row of this.ignores || []) {
                const u = row && row.user;
                const id = u && u.id;
                if (id == null) {
                    continue;
                }
                const n = Number(id);
                if (Number.isFinite(n)) {
                    s.add(n);
                }
            }

            return s;
        },
        /** T85: випадаючий список підказок (debounce + мін. 2 символи на бекенді). */
        peerAutocompletePanelOpen() {
            const q = String(this.peerLookupName || '').trim();
            if (q.length < 2) {
                return false;
            }

            return this.peerAutocompleteLoading || (this.peerAutocompleteSuggestions || []).length > 0;
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
            if (to !== 'users') {
                this.clearPeerAutocompleteUi();
            }
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
        peerLookupName() {
            this.schedulePeerAutocompleteFetch();
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
        rooms() {
            if (this.roomEditModalOpen && this.roomEditRoomId != null && !this.roomBeingEdited) {
                this.closeRoomEditModal();
            }
        },
        $route: {
            handler() {
                if (!this.chatBootstrapDone || !isChatRoute(this.$route)) {
                    return;
                }
                this.consumeOpenChatSettingsQuery();
                this.consumePrivatePeerFromRoute();
                const slug = this.$route.params.roomSlug;
                const newQ = this.$route.query || {};
                let targetId = null;
                if (slug != null && String(slug).trim() !== '') {
                    const bySlug = this.rooms.find((r) => String(r.slug) === String(slug));
                    if (bySlug) {
                        targetId = bySlug.room_id;
                    }
                } else if (newQ.room != null && String(newQ.room).trim() !== '') {
                    const raw = String(newQ.room).trim();
                    const n = Number(raw);
                    if (Number.isFinite(n) && raw === String(n)) {
                        if (this.rooms.some((r) => Number(r.room_id) === n)) {
                            targetId = n;
                        }
                    } else {
                        const byS = this.rooms.find((r) => String(r.slug) === raw);
                        if (byS) {
                            targetId = byS.room_id;
                        }
                    }
                }
                if (
                    targetId != null
                    && Number.isFinite(Number(targetId))
                    && Number(targetId) !== Number(this.selectedRoomId)
                    && this.rooms.some((r) => Number(r.room_id) === Number(targetId))
                ) {
                    this.selectedRoomId = Number(targetId);
                    void this.applyRoomSelection();

                    return;
                }
                if (newQ.focus_post != null && String(newQ.focus_post).trim() !== '') {
                    this.$nextTick(() => this.tryApplyFocusPostFromQuery());
                }
            },
            deep: true,
        },
        /** T65: favicon-бейдж = той самий лічильник, що й вкладка «Приват» (T56). */
        totalPrivateUnread(n) {
            setFaviconPrivateUnreadBadge(n);
        },
        browserDocumentTitleForChat() {
            this.syncChatDocumentTitle();
        },
        '$route.name'(name) {
            if (name === 'chat') {
                this.syncChatDocumentTitle();
            }
        },
    },
    created() {
        this.themeUi = getResolvedTheme();
        if (typeof window !== 'undefined' && window.matchMedia) {
            const mq = window.matchMedia('(max-width: 767px)');
            this.isNarrowViewport = mq.matches;
            this.panelOpen = !mq.matches;
        }
    },
    async mounted() {
        document.documentElement.setAttribute('data-theme', this.themeUi);
        this.attachChatSoundActivation();
        this.initViewportListener();
        await this.bootstrap();
        setFaviconPrivateUnreadBadge(this.totalPrivateUnread);
        this.syncChatDocumentTitle();
        this.$nextTick(() => {
            const c = this.$refs.chatComposer;
            if (c && typeof c.syncComposerInputHeight === 'function') {
                c.syncComposerInputHeight();
            }
        });
        window.addEventListener('keydown', this.onGlobalKeydown);
        this.onPageShowForFeedSyncBound = (e) => {
            if (
                e
                && e.persisted
                && this.chatBootstrapDone
                && isChatRoute(this.$route)
            ) {
                this.scheduleFeedSyncAfterResume();
            }
        };
        window.addEventListener('pageshow', this.onPageShowForFeedSyncBound);
    },
    beforeDestroy() {
        document.removeEventListener('mousedown', this.onSidebarBadgeMenuDocMouseDown, true);
        document.removeEventListener('keydown', this.onSidebarBadgeMenuDocKeydown, true);
        window.removeEventListener('keydown', this.onGlobalKeydown);
        if (this.onPageShowForFeedSyncBound) {
            window.removeEventListener('pageshow', this.onPageShowForFeedSyncBound);
            this.onPageShowForFeedSyncBound = null;
        }
        if (this.feedResumeSyncTimer !== null) {
            clearTimeout(this.feedResumeSyncTimer);
            this.feedResumeSyncTimer = null;
        }
        this.teardownMediaQuery();
        document.body.style.overflow = '';
        this.teardownEcho(true);
        this.stopPoll();
        if (this.markReadDebounceTimer) {
            clearTimeout(this.markReadDebounceTimer);
            this.markReadDebounceTimer = null;
        }
        if (this.peerLookupDebounceTimer) {
            clearTimeout(this.peerLookupDebounceTimer);
            this.peerLookupDebounceTimer = null;
        }
        this.detachChatSoundActivation();
        resetFaviconPrivateUnreadBadge();
    },
    methods: {
        ...chatRoomPrivateMethods,
        ...chatRoomFriendsIgnoresMethods,
        /** T196: після зняття ігнору перезавантажити стрічку (API знову віддає рядки автора). */
        async removeIgnore(userId) {
            await this.ensureSanctum();
            try {
                await window.axios.delete(`/api/v1/ignores/${userId}`);
                this.loadError = '';
                await this.loadFriendsAndIgnores();
                await this.loadMessages();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося зняти ігнор.';
            }
        },
        ...chatRoomPeerAutocompleteMethods,
        /** T93 */
        syncChatDocumentTitle() {
            if (typeof document === 'undefined') {
                return;
            }
            if (!this.$route || !isChatRoute(this.$route)) {
                return;
            }
            document.title = this.browserDocumentTitleForChat;
        },
        /** T65: autoplay — після першої взаємодії зі сторінкою. */
        attachChatSoundActivation() {
            if (typeof window === 'undefined') {
                return;
            }
            const onActivate = () => {
                markChatSoundUserActivated();
                this.detachChatSoundActivation();
            };
            this.chatSoundActivateHandler = onActivate;
            window.addEventListener('pointerdown', onActivate, { capture: true, once: true });
            window.addEventListener('keydown', onActivate, { capture: true, once: true });
        },
        detachChatSoundActivation() {
            if (typeof window === 'undefined' || !this.chatSoundActivateHandler) {
                return;
            }
            const h = this.chatSoundActivateHandler;
            window.removeEventListener('pointerdown', h, { capture: true });
            window.removeEventListener('keydown', h, { capture: true });
            this.chatSoundActivateHandler = null;
        },
        /** T65 + T123: newpost / mention — активна кімната; mention має пріоритет (один звук). */
        handleNewRoomMessageSound(m) {
            if (!m || !this.user) {
                return;
            }
            if (
                this.selectedRoomId == null
                || m.post_roomid == null
                || Number(m.post_roomid) !== Number(this.selectedRoomId)
            ) {
                return;
            }
            const legacyEveryPost = Boolean(
                this.chatSettings && this.chatSettings.sound_on_every_post,
            );
            const silent = Boolean(this.chatSettings && this.chatSettings.silent_mode);
            playActiveRoomIncomingSounds(this.user, {
                userId: m.user_id,
                mentionedUserIds: m.mentioned_user_ids,
                legacySoundEveryPost: legacyEveryPost,
                type: m.type,
                chatSilentMode: silent,
            });
        },
        initViewportListener() {
            if (typeof window === 'undefined' || !window.matchMedia) {
                return;
            }
            const mq = window.matchMedia('(max-width: 767px)');
            this.isNarrowViewport = mq.matches;
            /* На max-md панель off-canvas за замовчуванням закрита; на md+ лишається видимою колонкою. */
            this.panelOpen = !mq.matches;
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
                const tabs = side && side.$refs.sidebarTabBars;
                const btn =
                    (this.isNarrowViewport && tabs && tabs.$refs.panelCloseBtnMobile) ||
                    (tabs && tabs.$refs.panelCloseBtnDesktop) ||
                    (tabs && tabs.$refs.panelCloseBtnMobile);
                if (btn && typeof btn.focus === 'function') {
                    btn.focus();
                }
            });
        },
        /** Відкрити панель і перевести фокус на кнопку закриття (off-canvas). */
        beginOpeningPanel() {
            if (!this.panelOpen) {
                const el = document.activeElement;
                const mainCol = this.$refs.chatMainColumn;
                const header = mainCol && mainCol.$refs.chatRoomHeader;
                const mobile = header && header.$refs.mobilePanelToggle;
                const desktop = header && header.$refs.desktopPanelToggle;
                const isToggle =
                    (mobile && el === mobile) || (desktop && el === desktop);
                const main =
                    this.$el && typeof this.$el.querySelector === 'function'
                        ? this.$el.querySelector('#main-content')
                        : null;
                /* Після відкриття кнопки ховаються (T61) — не зберігати знятий з DOM елемент. */
                this.panelFocusReturnEl = isToggle && main ? main : el;
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
                await logoutAuth0IfLoggedIn();
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
        async ensureSanctum(options = {}) {
            const force = Boolean(options && options.force);
            if (!force && hasXsrfTokenCookie()) {
                return;
            }
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
        defaultFallbackRoom() {
            let r = this.rooms.find((x) => Number(x.room_id) === CHAT_DEFAULT_PUBLIC_ROOM_ID);
            if (!r) {
                r = this.rooms[0];
            }

            return r || null;
        },
        preserveSidebarRouteQuery() {
            const q = {};
            const fp = this.$route.query.focus_post;
            if (fp != null && String(fp).trim() !== '') {
                q.focus_post = String(fp);
            }
            const ocs = this.$route.query.open_chat_settings;
            if (ocs != null) {
                q.open_chat_settings = String(ocs);
            }

            return q;
        },
        async resolveInitialRoomFromRoute() {
            const slugParam = this.$route.params.roomSlug;
            const qRoom = this.$route.query.room;
            let picked = null;

            if (slugParam != null && String(slugParam).trim() !== '') {
                const s = String(slugParam);
                picked = this.rooms.find((r) => String(r.slug) === s);
                if (!picked) {
                    try {
                        await this.ensureSanctum();
                        const { data } = await window.axios.get(
                            `/api/v1/rooms/${encodeURIComponent(s)}/messages`,
                            { params: { limit: 1 } },
                        );
                        const rid = data.meta && data.meta.canonical_room_id;
                        if (rid != null) {
                            this.selectedRoomId = Number(rid);
                            await this.loadRooms();
                            picked = this.rooms.find((r) => Number(r.room_id) === Number(rid));
                        }
                    } catch (e) {
                        const st = e.response && e.response.status;
                        if (st === 403 || st === 404) {
                            const fb = this.defaultFallbackRoom();
                            if (fb) {
                                this.selectedRoomId = fb.room_id;
                                const loc = buildChatRoute(
                                    this.rooms,
                                    this.selectedRoomId,
                                    this.preserveSidebarRouteQuery(),
                                );
                                await this.$router.replace(loc).catch(() => {});
                                await this.applyRoomSelection();
                            }

                            return;
                        }
                    }
                }
            } else if (qRoom != null && String(qRoom).trim() !== '') {
                const raw = String(qRoom).trim();
                const n = Number(raw);
                if (Number.isFinite(n) && raw === String(n)) {
                    picked = this.rooms.find((r) => Number(r.room_id) === n);
                } else {
                    picked = this.rooms.find((r) => String(r.slug) === raw);
                }
            }

            if (!picked && this.rooms.length > 0) {
                picked = this.defaultFallbackRoom();
            }

            if (!picked) {
                this.selectedRoomId = null;

                return;
            }

            this.selectedRoomId = picked.room_id;
            const loc = buildChatRoute(this.rooms, this.selectedRoomId, this.preserveSidebarRouteQuery());
            await this.$router.replace(loc).catch(() => {});
            await this.applyRoomSelection();
        },
        async bootstrap() {
            try {
                await ensureAuth0BootstrapFromLandingApi();
                let user = await this.fetchUser();
                if (!user) {
                    const deepSlug = this.$route.params.roomSlug;
                    if (deepSlug != null && String(deepSlug).trim() !== '') {
                        await this.ensureSanctum();
                        try {
                            await window.axios.post('/api/v1/auth/guest', {});
                            user = await this.fetchUser();
                        } catch {
                            /* ignore */
                        }
                    }
                    if (!user) {
                        await this.$router.replace({ path: '/' });

                        return;
                    }
                }
                this.user = user;

                if (this.user.requires_password_setup) {
                    await this.$router.replace({ name: 'legacy-password-setup' });

                    return;
                }

                await Promise.all([this.loadRooms(), this.loadChatSettings(), loadChatEmoticonsCatalog()]);
                await this.resolveInitialRoomFromRoute();

                await Promise.all([this.loadConversations(), this.loadFriendsAndIgnores()]);
                this.pruneIgnoredMessagesFromFeed();
            } finally {
                this.chatBootstrapDone = true;
                this.$nextTick(() => {
                    this.consumePrivatePeerFromRoute();
                    this.consumeOpenChatSettingsQuery();
                });
            }
        },
        async loadChatSettings() {
            try {
                const { data } = await window.axios.get('/api/v1/chat/settings');
                this.chatSettings = data.data || null;
            } catch {
                this.chatSettings = null;
            }
        },
        /**
         * T75 / T194: deep link `?open_chat_settings=1` — відкрити модал (адмін) і прибрати параметр з URL.
         * Під час bootstrap `$route`-watcher ще не встигає (chatBootstrapDone), тому виклик також з `bootstrap()`.
         */
        consumeOpenChatSettingsQuery() {
            if (!this.user || this.user.chat_role !== 'admin') {
                return;
            }
            const raw = this.$route.query.open_chat_settings;
            if (raw == null || String(raw).trim() === '') {
                return;
            }
            if (['0', 'false', 'no', 'off'].includes(String(raw).trim().toLowerCase())) {
                return;
            }
            const q = { ...this.$route.query };
            delete q.open_chat_settings;
            this.$router.replace(buildChatRoute(this.rooms, this.selectedRoomId, q)).catch(() => {});
            this.chatSettingsModalOpen = true;
        },
        /** T194: прибрати `open_chat_settings` з адресного рядка (закриття / збереження без F5-реопену). */
        stripOpenChatSettingsFromRouteIfPresent() {
            if (!isChatRoute(this.$route)) {
                return;
            }
            const raw = this.$route.query.open_chat_settings;
            if (raw == null || String(raw).trim() === '') {
                return;
            }
            const q = { ...this.$route.query };
            delete q.open_chat_settings;
            this.$router.replace(buildChatRoute(this.rooms, this.selectedRoomId, q)).catch(() => {});
        },
        onChatSettingsModalClose() {
            this.chatSettingsModalOpen = false;
            this.stripOpenChatSettingsFromRouteIfPresent();
        },
        async onChatSettingsModalSaved() {
            await this.loadChatSettings();
            this.stripOpenChatSettingsFromRouteIfPresent();
        },
        /** T166: deep link з Web Push — `?private_peer=&private_peer_name=` (ім’я лише для швидкого UI). */
        consumePrivatePeerFromRoute() {
            if (!this.chatBootstrapDone || !this.user || this.user.guest) {
                return;
            }
            const raw = this.$route.query.private_peer;
            if (raw == null || String(raw).trim() === '') {
                return;
            }
            const id = Number(String(raw).trim());
            if (!Number.isFinite(id) || id <= 0) {
                return;
            }
            let userName = '';
            const nameRaw = this.$route.query.private_peer_name;
            if (nameRaw != null && String(nameRaw).trim() !== '') {
                try {
                    userName = decodeURIComponent(String(nameRaw));
                } catch {
                    userName = String(nameRaw);
                }
            }
            if (!userName) {
                userName = 'Користувач';
            }
            this.openPrivatePeer({ id, user_name: userName });
            const q = { ...this.$route.query };
            delete q.private_peer;
            delete q.private_peer_name;
            this.$router.replace(buildChatRoute(this.rooms, this.selectedRoomId, q)).catch(() => {});
        },
        openAddRoomModal() {
            this.addRoomError = '';
            this.addRoomModalOpen = true;
        },
        openRoomEditor(roomId) {
            if (roomId == null) {
                return;
            }
            this.editRoomError = '';
            this.roomEditRoomId = roomId;
            this.roomEditModalOpen = true;
        },
        closeRoomEditModal() {
            this.roomEditModalOpen = false;
            this.roomEditRoomId = null;
            this.editRoomError = '';
        },
        async onAddRoomModalCreate({ room_name, topic }) {
            this.addRoomError = '';
            this.creatingRoom = true;
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.post('/api/v1/rooms', { room_name, topic });
                const created = data.data;
                await this.loadRooms();
                if (created && created.room_id) {
                    this.selectedRoomId = created.room_id;
                    await this.$router
                        .replace(buildChatRoute(this.rooms, created.room_id, this.preserveSidebarRouteQuery()))
                        .catch(() => {});
                    await this.applyRoomSelection();
                }
                const u = await this.fetchUser();
                if (u) {
                    this.user = u;
                }
                this.addRoomModalOpen = false;
            } catch (e) {
                const st = e.response && e.response.status;
                const msg =
                    (e.response && e.response.data && e.response.data.message) ||
                    (st === 403 ? 'Немає права створити кімнату.' : null) ||
                    'Не вдалося створити кімнату.';
                this.addRoomError = typeof msg === 'string' ? msg : 'Не вдалося створити кімнату.';
            } finally {
                this.creatingRoom = false;
            }
        },
        async onRoomEditModalSave(payload) {
            const roomId = payload && payload.room_id;
            if (roomId == null) {
                return;
            }
            const body = {};
            if (Object.prototype.hasOwnProperty.call(payload, 'room_name')) {
                body.room_name = payload.room_name;
            }
            if (Object.prototype.hasOwnProperty.call(payload, 'topic')) {
                body.topic = payload.topic;
            }
            if (Object.prototype.hasOwnProperty.call(payload, 'access')) {
                body.access = payload.access;
            }
            if (Object.prototype.hasOwnProperty.call(payload, 'ai_bot_enabled')) {
                body.ai_bot_enabled = payload.ai_bot_enabled;
            }
            this.editRoomError = '';
            this.roomEditSaving = true;
            try {
                await this.ensureSanctum();
                await window.axios.patch(`/api/v1/rooms/${roomId}`, body);
                await this.loadRooms();
            } catch (e) {
                const st = e.response && e.response.status;
                const msg =
                    (e.response && e.response.data && e.response.data.message) ||
                    (st === 403 ? 'Недостатньо прав для зміни цієї кімнати.' : null) ||
                    'Не вдалося зберегти зміни.';
                this.editRoomError = typeof msg === 'string' ? msg : 'Не вдалося зберегти зміни.';
            } finally {
                this.roomEditSaving = false;
            }
        },
        onRoomEditRequestDelete(roomId) {
            if (roomId == null) {
                return;
            }
            this.pendingDeleteRoomId = roomId;
            this.deleteRoomConfirmOpen = true;
        },
        closeDeleteRoomConfirm() {
            this.deleteRoomConfirmOpen = false;
            this.pendingDeleteRoomId = null;
        },
        async confirmDeleteRoom() {
            const id = this.pendingDeleteRoomId;
            if (id == null) {
                this.closeDeleteRoomConfirm();

                return;
            }
            this.editRoomError = '';
            this.roomEditDeleting = true;
            try {
                await this.ensureSanctum();
                await window.axios.delete(`/api/v1/rooms/${id}`);
                const wasSelected = this.selectedRoomId === id;
                await this.loadRooms();
                this.closeDeleteRoomConfirm();
                if (this.roomEditRoomId === id) {
                    this.closeRoomEditModal();
                }
                if (wasSelected) {
                    if (this.rooms.length > 0) {
                        this.selectedRoomId = this.rooms[0].room_id;
                        await this.$router
                            .replace(
                                buildChatRoute(this.rooms, this.selectedRoomId, this.preserveSidebarRouteQuery()),
                            )
                            .catch(() => {});
                        await this.applyRoomSelection();
                    } else {
                        this.selectedRoomId = null;
                        this.clearMessages();
                        await this.$router.replace({ name: 'chat', query: {} }).catch(() => {});
                    }
                }
            } catch (e) {
                const st = e.response && e.response.status;
                let msg =
                    (e.response && e.response.data && e.response.data.message) ||
                    (st === 403 ? 'Немає права видалити цю кімнату.' : null) ||
                    'Не вдалося видалити кімнату.';
                this.editRoomError = typeof msg === 'string' ? msg : 'Не вдалося видалити кімнату.';
                this.closeDeleteRoomConfirm();
            } finally {
                this.roomEditDeleting = false;
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
        /** T162: узгоджено з App\Chat\ChatRole::postColorClass(). */
        viewerPostColorClass() {
            const u = this.user;
            if (!u) {
                return 'user';
            }
            if (u.guest) {
                return 'guest';
            }
            const r = u.chat_role;
            if (r === 'admin') {
                return 'admin';
            }
            if (r === 'moderator') {
                return 'mod';
            }
            if (r === 'vip') {
                return 'vip';
            }

            return 'user';
        },
        removePendingRoomMessageByClientId(clientMessageId) {
            if (!clientMessageId) {
                return;
            }
            const idx = this.messages.findIndex(
                (x) =>
                    x.client_message_id === clientMessageId && Number(x.post_id) < 0,
            );
            if (idx === -1) {
                return;
            }
            this.messages.splice(idx, 1);
        },
        buildOptimisticPublicMessage({ text, stylePayload, clientMessageId, imageId, tempPostId }) {
            if (!this.user || imageId || !clientMessageId || tempPostId == null) {
                return null;
            }
            const trimmed = String(text || '').trim();
            if (!trimmed || trimmed.startsWith('/')) {
                return null;
            }
            const ts = Math.floor(Date.now() / 1000);
            const raw = {
                post_id: tempPostId,
                post_roomid: this.selectedRoomId,
                user_id: this.user.id,
                post_date: ts,
                post_edited_at: null,
                post_deleted_at: null,
                post_time: null,
                post_user: this.user.user_name,
                post_message: text,
                post_style: stylePayload || { bold: false, italic: false, underline: false },
                post_color: this.viewerPostColorClass(),
                type: 'public',
                recipient_user_id: null,
                client_message_id: clientMessageId,
                avatar: this.user.avatar_url != null ? String(this.user.avatar_url) : '',
                file: 0,
                image: null,
                can_edit: false,
                can_delete: false,
            };
            const m = normalizeMessage(raw);
            if (!m) {
                return null;
            }
            m.rp_send_pending = true;

            return m;
        },
        inferCanDeleteForMessage(m) {
            if (!this.user || this.user.guest) {
                return false;
            }
            if (m.post_deleted_at != null && m.post_deleted_at !== '') {
                return false;
            }
            if (m.type === 'client_only') {
                return Number(m.user_id) === Number(this.user.id);
            }
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
            // Творець кімнати (T58): чужі публічні повідомлення в своїй кімнаті — без постів staff.
            if (
                m.post_roomid != null
                && Number(m.post_roomid) === Number(this.selectedRoomId)
            ) {
                const room = this.rooms.find((r) => Number(r.room_id) === Number(this.selectedRoomId));
                const cid = room && room.created_by_user_id;
                if (cid != null && Number(cid) === Number(this.user.id)) {
                    if (m.post_color === 'admin' || m.post_color === 'mod') {
                        return false;
                    }
                    if (Number(m.user_id) !== Number(this.user.id)) {
                        return true;
                    }
                }
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
        shouldHideRoomMessageDueToIgnore(m) {
            if (!this.user || this.user.guest || !m) {
                return false;
            }
            const t = m.type;
            if (t !== 'public' && t !== 'inline_private') {
                return false;
            }
            const uid = Number(m.user_id);
            if (!Number.isFinite(uid)) {
                return false;
            }
            if (uid === Number(this.user.id)) {
                return false;
            }

            return this.ignoredUserIdsSet.has(uid);
        },
        pruneIgnoredMessagesFromFeed() {
            const next = [];
            const nextIds = new Set();
            for (const m of this.messages || []) {
                if (this.shouldHideRoomMessageDueToIgnore(m)) {
                    continue;
                }
                next.push(m);
                if (m && m.post_id != null) {
                    nextIds.add(m.post_id);
                }
            }
            this.messages = next;
            this.messageIds = nextIds;
        },
        mergeMessage(raw, options = {}) {
            const suppressAutoScroll = Boolean(options && options.suppressAutoScroll);
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
            if (this.shouldHideRoomMessageDueToIgnore(m)) {
                return;
            }
            if (m.client_message_id) {
                const pendIdx = this.messages.findIndex(
                    (x) =>
                        x.client_message_id === m.client_message_id
                        && Number(x.post_id) < 0,
                );
                if (pendIdx !== -1) {
                    const prev = this.messages[pendIdx];
                    const next = { ...prev, ...m };
                    delete next.rp_send_pending;
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
                    this.$set(this.messages, pendIdx, next);
                    this.messageIds.add(m.post_id);
                    this.messages.sort((a, b) => Number(a.post_id) - Number(b.post_id));
                    if (!suppressAutoScroll) {
                        this.$nextTick(() => this.scrollToBottom());
                    }

                    return;
                }
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
                if (!suppressAutoScroll) {
                    this.$nextTick(() => this.scrollToBottom());
                }

                return;
            }
            if (this.messageIds.has(m.post_id)) {
                return;
            }
            this.messageIds.add(m.post_id);
            this.messages.push(m);
            this.messages.sort((a, b) => a.post_id - b.post_id);
            if (!suppressAutoScroll) {
                this.$nextTick(() => this.scrollToBottom());
            }
        },
        scrollToBottom() {
            const feed = this.$refs.chatFeed;
            if (feed && typeof feed.scrollToBottom === 'function') {
                feed.scrollToBottom();
            }
        },
        /** T60: після завантаження — `focus_post` у query або низ стрічки. */
        afterMessagesLoadedScroll() {
            const fpRaw = this.$route.query.focus_post;
            const pid =
                fpRaw != null && String(fpRaw).trim() !== ''
                    ? Number(fpRaw)
                    : null;
            if (pid != null && Number.isFinite(pid) && this.messages.some((m) => Number(m.post_id) === pid)) {
                const feed = this.$refs.chatFeed;
                if (feed && typeof feed.scrollToPost === 'function') {
                    feed.scrollToPost(pid);
                }
                this.stripFocusPostFromRoute();

                return;
            }
            if (pid != null && Number.isFinite(pid)) {
                this.stripFocusPostFromRoute();
            }
            this.scrollToBottom();
        },
        stripFocusPostFromRoute() {
            if (this.$route.query.focus_post == null) {
                return;
            }
            const q = { ...this.$route.query };
            delete q.focus_post;
            this.$router.replace(buildChatRoute(this.rooms, this.selectedRoomId, q)).catch(() => {});
        },
        tryApplyFocusPostFromQuery() {
            if (!this.chatBootstrapDone || !isChatRoute(this.$route) || this.loadError) {
                return;
            }
            const fpRaw = this.$route.query.focus_post;
            if (fpRaw == null || String(fpRaw).trim() === '') {
                return;
            }
            if (this.loadingMessages) {
                return;
            }
            const pid = Number(fpRaw);
            if (!Number.isFinite(pid)) {
                this.stripFocusPostFromRoute();

                return;
            }
            if (!this.messages.some((m) => Number(m.post_id) === pid)) {
                this.stripFocusPostFromRoute();

                return;
            }
            const feed = this.$refs.chatFeed;
            if (feed && typeof feed.scrollToPost === 'function') {
                feed.scrollToPost(pid);
            }
            this.stripFocusPostFromRoute();
        },
        async loadMessages() {
            if (!this.selectedRoomId || !this.selectedRoomApiSegment) {
                return;
            }
            this.loadingMessages = true;
            try {
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomApiSegment}/messages`,
                    { params: { limit: 80 } },
                );
                if (data.meta && data.meta.slug_redirect && data.meta.canonical_slug) {
                    const q = { ...this.$route.query };
                    delete q.room;
                    await this.$router
                        .replace({
                            name: 'chat',
                            params: { roomSlug: String(data.meta.canonical_slug) },
                            query: q,
                        })
                        .catch(() => {});
                }
                this.clearMessages();
                (data.data || []).forEach((row) => this.mergeMessage(row));
                this.olderRoomCursor = data.meta ? data.meta.next_cursor : null;
                this.olderRoomHasMore = Boolean(data.meta && data.meta.has_more_older);
                this.loadingOlderRoom = false;

                const lr =
                    data.meta && data.meta.last_read_post_id != null
                        ? Number(data.meta.last_read_post_id)
                        : null;
                this.newMsgDividerDismissed = false;
                if (lr == null || !Number.isFinite(lr)) {
                    this.newMsgDividerBeforePostId = null;
                } else {
                    const rows = data.data || [];
                    const first = rows.find((row) => Number(row.post_id) > lr);
                    this.newMsgDividerBeforePostId = first ? Number(first.post_id) : null;
                }
                this.roomReadSuppressBottomUntil = Date.now() + 700;
            } catch {
                this.loadError = 'Не вдалося завантажити повідомлення.';
            } finally {
                this.loadingMessages = false;
                this.$nextTick(() => this.afterMessagesLoadedScroll());
            }
        },
        onFeedTopVisible() {
            void this.loadOlderRoomMessages();
        },
        async loadOlderRoomMessages() {
            if (!this.selectedRoomId || !this.selectedRoomApiSegment) {
                return;
            }
            if (this.loadingMessages || this.loadingOlderRoom) {
                return;
            }
            if (!this.olderRoomHasMore || !this.olderRoomCursor) {
                return;
            }
            const feed = this.$refs.chatFeed;
            const container = feed && feed.$refs ? feed.$refs.scrollContainer : null;
            const prevHeight = container ? container.scrollHeight : 0;
            const prevTop = container ? container.scrollTop : 0;

            this.loadingOlderRoom = true;
            try {
                const requestedCursor = this.olderRoomCursor;
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomApiSegment}/messages`,
                    { params: { before: requestedCursor, limit: this.roomHistoryChunkSize } },
                );
                const rows = Array.isArray(data.data) ? data.data : [];
                for (const row of rows) {
                    this.mergeMessage(row, { suppressAutoScroll: true });
                }
                const nextCursor = data.meta ? data.meta.next_cursor : null;
                const hasMoreOlder = Boolean(data.meta && data.meta.has_more_older);
                const cursorProgressed = nextCursor != null && Number(nextCursor) !== Number(requestedCursor);
                this.olderRoomCursor = nextCursor;
                this.olderRoomHasMore = hasMoreOlder && cursorProgressed;
                this.$nextTick(() => {
                    if (!container) {
                        return;
                    }
                    const nextHeight = container.scrollHeight;
                    const delta = nextHeight - prevHeight;
                    if (delta > 0) {
                        container.scrollTop = prevTop + delta;
                    }
                });
            } catch {
                /* ignore */
            } finally {
                this.loadingOlderRoom = false;
            }
        },
        onFeedBottomVisible() {
            if (!this.selectedRoomId || !this.messages.length) {
                return;
            }
            this.newMsgDividerDismissed = true;
            const latest = this.messages[this.messages.length - 1].post_id;
            this.scheduleMarkRoomRead(latest);
        },
        scheduleMarkRoomRead(postId) {
            if (this.markReadDebounceTimer) {
                clearTimeout(this.markReadDebounceTimer);
            }
            const rid = this.selectedRoomId;
            const pid = Number(postId);
            this.markReadDebounceTimer = setTimeout(async () => {
                this.markReadDebounceTimer = null;
                if (!rid || !Number.isFinite(pid)) {
                    return;
                }
                const seg = apiRoomPathSegment(this.rooms, rid);
                if (!seg) {
                    return;
                }
                try {
                    await this.ensureSanctum();
                    await window.axios.post(`/api/v1/rooms/${seg}/read`, {
                        last_read_post_id: pid,
                    });
                } catch {
                    /* ignore */
                }
            }, 450);
        },
        /**
         * T170 + T06: підтягнути останню сторінку повідомлень з REST і змерджити (dedupe в mergeMessage).
         * @param {{ playSoundsForNew?: boolean }} [options]
         */
        async fetchLatestRoomMessagesMerged(options = {}) {
            const playSoundsForNew = options.playSoundsForNew !== false;
            if (!this.selectedRoomId || !this.selectedRoomApiSegment) {
                return;
            }
            if (this.loadingMessages) {
                return;
            }
            try {
                await Promise.all([
                    this.fetchPeerPresenceStatuses(null),
                    this.fetchPeerSexHints(),
                ]);
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomApiSegment}/messages`,
                    { params: { limit: 80 } },
                );
                const prevIds = new Set(this.messageIds);
                for (const row of data.data || []) {
                    const m = normalizeMessage(row);
                    const wasNew = Boolean(m && !prevIds.has(m.post_id));
                    this.mergeMessage(row);
                    if (playSoundsForNew && wasNew && m) {
                        this.handleNewRoomMessageSound(m);
                    }
                    if (m) {
                        prevIds.add(m.post_id);
                    }
                }
            } catch {
                /* ignore */
            }
        },
        async pollNewMessages() {
            if (!this.wsDegraded) {
                return;
            }
            await this.fetchLatestRoomMessagesMerged({ playSoundsForNew: true });
        },
        /** T170: після кліку по Web Push / повернення вкладки — синхронізувати стрічку з сервером. */
        scheduleFeedSyncAfterResume() {
            if (typeof window === 'undefined') {
                return;
            }
            if (this.feedResumeSyncTimer !== null) {
                clearTimeout(this.feedResumeSyncTimer);
            }
            this.feedResumeSyncTimer = window.setTimeout(() => {
                this.feedResumeSyncTimer = null;
                void this.runFeedSyncAfterResume();
            }, 400);
        },
        async runFeedSyncAfterResume() {
            if (!this.chatBootstrapDone || !isChatRoute(this.$route) || !this.user || this.user.guest) {
                return;
            }
            try {
                await this.ensureSanctum();
            } catch {
                return;
            }
            if (this.privatePeer) {
                if (!this.loadingPrivateMessages) {
                    await this.loadPrivateMessages();
                }

                return;
            }
            await this.fetchLatestRoomMessagesMerged({ playSoundsForNew: false });
        },
        onPrivateTopVisible() {
            void this.loadOlderPrivateMessages();
        },
        async loadOlderPrivateMessages() {
            if (!this.privatePeer) {
                return;
            }
            if (this.loadingPrivateMessages || this.loadingOlderPrivate) {
                return;
            }
            if (!this.olderPrivateHasMore || !this.olderPrivateCursor) {
                return;
            }
            const panel = this.$refs.privateChatPanel;
            const container = panel && panel.$refs ? panel.$refs.privateList : null;
            const prevHeight = container ? container.scrollHeight : 0;
            const prevTop = container ? container.scrollTop : 0;

            this.loadingOlderPrivate = true;
            try {
                const requestedCursor = this.olderPrivateCursor;
                const { data } = await window.axios.get(
                    `/api/v1/private/peers/${this.privatePeer.id}/messages`,
                    { params: { before: requestedCursor, limit: this.privateHistoryChunkSize } },
                );
                const rows = Array.isArray(data.data) ? data.data : [];
                const newOnes = rows
                    .filter((r) => r && typeof r.id !== 'undefined' && !this.privateMessageIds.has(r.id))
                    .map((r) => ({
                        id: r.id,
                        sender_id: r.sender_id,
                        recipient_id: r.recipient_id,
                        body: r.body,
                        sent_at: r.sent_at != null && r.sent_at !== '' ? Number(r.sent_at) : r.sent_at,
                        sent_time: r.sent_time,
                        client_message_id: r.client_message_id,
                    }));
                if (newOnes.length) {
                    newOnes.sort((a, b) => a.id - b.id);
                    for (const m of newOnes) {
                        this.privateMessageIds.add(m.id);
                    }
                    this.privateMessages = [...newOnes, ...this.privateMessages];
                }
                const nextCursor = data.meta ? data.meta.next_cursor : null;
                const hasMoreOlder = Boolean(data.meta && data.meta.has_more_older);
                const cursorProgressed = nextCursor != null && Number(nextCursor) !== Number(requestedCursor);
                this.olderPrivateCursor = nextCursor;
                this.olderPrivateHasMore = hasMoreOlder && cursorProgressed;
                this.$nextTick(() => {
                    if (!container) {
                        return;
                    }
                    const nextHeight = container.scrollHeight;
                    const delta = nextHeight - prevHeight;
                    if (delta > 0) {
                        container.scrollTop = prevTop + delta;
                    }
                });
            } catch {
                /* ignore */
            } finally {
                this.loadingOlderPrivate = false;
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
        computeLocalPresenceStatus() {
            if (typeof window === 'undefined') {
                return 'online';
            }
            const idleSec = (Date.now() - (this.presenceLastActivityAt || 0)) / 1000;
            if (idleSec >= PRESENCE_INACTIVE_IDLE_SEC) {
                return 'inactive';
            }
            if (this.documentHiddenFlag) {
                return 'away';
            }
            if (idleSec >= PRESENCE_AWAY_IDLE_SEC) {
                return 'away';
            }

            return 'online';
        },
        startRoomPresenceTracking() {
            if (typeof window === 'undefined' || !this.user || this.selectedRoomId == null) {
                return;
            }
            if (this.presenceTickTimer !== null) {
                return;
            }
            this.presenceLastActivityAt = Date.now();
            this.documentHiddenFlag = document.visibilityState !== 'visible';
            this.onPresenceVisibilityBound = () => {
                const nowVisible = document.visibilityState === 'visible';
                const wasHidden = this.documentHiddenFlag;
                this.documentHiddenFlag = !nowVisible;
                if (nowVisible) {
                    this.presenceLastActivityAt = Date.now();
                    if (
                        wasHidden
                        && this.chatBootstrapDone
                        && isChatRoute(this.$route)
                        && this.user
                        && !this.user.guest
                    ) {
                        this.scheduleFeedSyncAfterResume();
                    }
                }
                this.postSelfPresenceStatusIfNeeded(this.computeLocalPresenceStatus());
            };
            document.addEventListener('visibilitychange', this.onPresenceVisibilityBound);
            this.onPresenceUserActivityBound = () => {
                this.presenceLastActivityAt = Date.now();
                if (this.presenceActivityDebounceTimer) {
                    clearTimeout(this.presenceActivityDebounceTimer);
                }
                this.presenceActivityDebounceTimer = setTimeout(() => {
                    this.presenceActivityDebounceTimer = null;
                    this.postSelfPresenceStatusIfNeeded(this.computeLocalPresenceStatus());
                }, 400);
            };
            this.presenceUserActivityListenerOpts = { passive: true };
            ['mousedown', 'keydown', 'touchstart', 'wheel'].forEach((evt) => {
                window.addEventListener(evt, this.onPresenceUserActivityBound, this.presenceUserActivityListenerOpts);
            });
            this.presenceTickTimer = window.setInterval(() => {
                this.postSelfPresenceStatusIfNeeded(this.computeLocalPresenceStatus());
            }, 15000);
            this.postSelfPresenceStatusIfNeeded(this.computeLocalPresenceStatus());
        },
        stopRoomPresenceTracking() {
            if (this.presenceTickTimer !== null) {
                clearInterval(this.presenceTickTimer);
                this.presenceTickTimer = null;
            }
            if (this.presenceActivityDebounceTimer) {
                clearTimeout(this.presenceActivityDebounceTimer);
                this.presenceActivityDebounceTimer = null;
            }
            if (this.presenceFetchDebounceTimer) {
                clearTimeout(this.presenceFetchDebounceTimer);
                this.presenceFetchDebounceTimer = null;
            }
            if (this.onPresenceVisibilityBound) {
                document.removeEventListener('visibilitychange', this.onPresenceVisibilityBound);
                this.onPresenceVisibilityBound = null;
            }
            if (this.onPresenceUserActivityBound) {
                const o = this.presenceUserActivityListenerOpts || { passive: true };
                ['mousedown', 'keydown', 'touchstart', 'wheel'].forEach((evt) => {
                    window.removeEventListener(evt, this.onPresenceUserActivityBound, o);
                });
                this.onPresenceUserActivityBound = null;
                this.presenceUserActivityListenerOpts = null;
            }
            this.presenceLastSentStatus = null;
            this.presenceLastPostedAt = null;
        },
        /**
         * @param {number} presenceFetchEpoch Лічильник після increment у syncHere/addPeer (не передавати з poll).
         * @param {boolean} immediate — після `here()` без debounce; `joining` — debounce `PEER_PRESENCE_JOIN_DEBOUNCE_MS`.
         */
        scheduleFetchPeerPresenceStatuses(presenceFetchEpoch, immediate = false) {
            if (this.presenceFetchDebounceTimer) {
                clearTimeout(this.presenceFetchDebounceTimer);
                this.presenceFetchDebounceTimer = null;
            }
            const runFetches = async () => {
                try {
                    await this.ensureSanctum();
                } catch {
                    if (
                        presenceFetchEpoch != null &&
                        presenceFetchEpoch === this.peerPresenceStatusFetchEpoch
                    ) {
                        this.peerPresenceStatusFetchLoading = false;
                    }

                    return;
                }
                await Promise.all([
                    this.fetchPeerPresenceStatuses(presenceFetchEpoch, { skipEnsure: true }),
                    this.fetchPeerSexHints({ skipEnsure: true }),
                ]).catch(() => {});
            };
            if (immediate) {
                void runFetches();

                return;
            }
            this.presenceFetchDebounceTimer = setTimeout(() => {
                this.presenceFetchDebounceTimer = null;
                void runFetches();
            }, PEER_PRESENCE_JOIN_DEBOUNCE_MS);
        },
        /**
         * @param {number|null} presenceFetchEpoch Поточний лічильник з syncHere/addPeer; **`null`** — виклики
         *   на кшталт poll при wsDegraded: **не** змінюють `peerPresenceStatusFetchLoading`.
         * @param {{ skipEnsure?: boolean }} [options]
         */
        async fetchPeerPresenceStatuses(presenceFetchEpoch, options = {}) {
            const skipEnsure = Boolean(options.skipEnsure);
            if (!this.selectedRoomId || !this.selectedRoomApiSegment || !this.roomPresencePeers.length) {
                if (
                    presenceFetchEpoch != null &&
                    presenceFetchEpoch === this.peerPresenceStatusFetchEpoch
                ) {
                    this.peerPresenceStatusFetchLoading = false;
                }

                return;
            }
            const ids = this.roomPresencePeers.map((p) => p.id).join(',');
            try {
                if (!skipEnsure) {
                    await this.ensureSanctum();
                }
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomApiSegment}/presence-statuses`,
                    { params: { user_ids: ids } },
                );
                const map = data && data.data && typeof data.data === 'object' ? data.data : {};
                this.peerPresenceStatusByUserId = { ...this.peerPresenceStatusByUserId, ...map };
            } catch {
                /* ignore */
            } finally {
                if (
                    presenceFetchEpoch != null &&
                    presenceFetchEpoch === this.peerPresenceStatusFetchEpoch
                ) {
                    this.peerPresenceStatusFetchLoading = false;
                }
            }
        },
        async fetchPeerSexHints(options = {}) {
            const skipEnsure = Boolean(options.skipEnsure);
            if (!this.selectedRoomId || !this.selectedRoomApiSegment || !this.roomPresencePeers.length) {
                return;
            }
            if (!this.user || this.user.guest) {
                this.peerSexHintsByUserId = {};

                return;
            }
            const ids = this.roomPresencePeers.map((p) => p.id).join(',');
            try {
                if (!skipEnsure) {
                    await this.ensureSanctum();
                }
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomApiSegment}/peer-hints`,
                    { params: { user_ids: ids } },
                );
                const map = data && data.data && typeof data.data === 'object' ? data.data : {};
                this.peerSexHintsByUserId = { ...this.peerSexHintsByUserId, ...map };
            } catch {
                /* ignore */
            }
        },
        async postSelfPresenceStatusIfNeeded(computedStatus) {
            if (!this.user || this.selectedRoomId == null || !this.selectedRoomApiSegment) {
                return;
            }
            const now = Date.now();
            const needHeartbeat =
                this.presenceLastPostedAt == null || now - this.presenceLastPostedAt >= 45000;
            if (computedStatus === this.presenceLastSentStatus && !needHeartbeat) {
                return;
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/rooms/${this.selectedRoomApiSegment}/presence-status`, {
                    status: computedStatus,
                });
                this.presenceLastSentStatus = computedStatus;
                this.presenceLastPostedAt = now;
            } catch {
                /* ignore */
            }
        },
        syncPresenceHere(users) {
            const myId = this.user && this.user.id != null ? Number(this.user.id) : null;
            const list = (users || [])
                .map((u) => normalizePresencePeer(u))
                .filter((p) => p && myId !== null && p.id !== myId && !p.presence_invisible);
            list.sort((a, b) => a.user_name.localeCompare(b.user_name, 'uk'));
            this.peerPresenceStatusFetchEpoch += 1;
            const presenceFetchEpoch = this.peerPresenceStatusFetchEpoch;
            this.peerPresenceStatusFetchLoading = true;
            this.roomPresencePeers = list;
            this.$nextTick(() => this.scheduleFetchPeerPresenceStatuses(presenceFetchEpoch, true));
        },
        addPresencePeer(raw) {
            const p = normalizePresencePeer(raw);
            const myId = this.user && this.user.id != null ? Number(this.user.id) : null;
            if (!p || myId === null || p.id === myId || p.presence_invisible) {
                return;
            }
            if (this.roomPresencePeers.some((x) => x.id === p.id)) {
                return;
            }
            this.peerPresenceStatusFetchEpoch += 1;
            const presenceFetchEpoch = this.peerPresenceStatusFetchEpoch;
            this.peerPresenceStatusFetchLoading = true;
            const next = [...this.roomPresencePeers, p];
            next.sort((a, b) => a.user_name.localeCompare(b.user_name, 'uk'));
            this.roomPresencePeers = next;
            this.scheduleFetchPeerPresenceStatuses(presenceFetchEpoch);
        },
        removePresencePeer(raw) {
            const id = raw && raw.id != null ? Number(raw.id) : null;
            if (id == null) {
                return;
            }
            this.roomPresencePeers = this.roomPresencePeers.filter((x) => x.id !== id);
            const key = String(id);
            if (this.peerPresenceStatusByUserId[key] !== undefined) {
                const nextMap = { ...this.peerPresenceStatusByUserId };
                delete nextMap[key];
                this.peerPresenceStatusByUserId = nextMap;
            }
            if (this.peerSexHintsByUserId[key] !== undefined) {
                const nextHints = { ...this.peerSexHintsByUserId };
                delete nextHints[key];
                this.peerSexHintsByUserId = nextHints;
            }
        },
        teardownEcho(fullDisconnect = false) {
            this.stopRoomPresenceTracking();
            this.peerPresenceStatusByUserId = {};
            this.peerSexHintsByUserId = {};
            this.peerPresenceStatusFetchLoading = false;
            this.peerPresenceStatusFetchEpoch = 0;
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
        async setupEcho() {
            this.teardownEcho(false);

            let echo = this.echo;
            if (!echo) {
                const bearer = await getAuth0AccessTokenSilentlyOrNull();
                echo = createEcho(bearer || null);
                if (!echo) {
                    this.wsDegraded = true;
                    this.startPollIfDegraded();
                    this.startRoomPresenceTracking();

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
                this.startRoomPresenceTracking();
            });

            channel.error(() => {
                this.wsDegraded = true;
                this.roomPresencePeers = [];
                this.peerPresenceStatusByUserId = {};
                this.peerSexHintsByUserId = {};
                this.peerPresenceStatusFetchLoading = false;
                this.peerPresenceStatusFetchEpoch = 0;
                this.startPollIfDegraded();
            });

            channel.listen('.MessagePosted', (payload) => {
                const m = normalizeMessage(payload);
                const wasNew = Boolean(m && !this.messageIds.has(m.post_id));
                this.mergeMessage(payload);
                if (wasNew) {
                    this.handleNewRoomMessageSound(m);
                }
            });

            channel.listen('.MessageUpdated', (payload) => {
                this.mergeMessage(payload);
            });

            channel.listen('.MessageDeleted', (payload) => {
                this.mergeMessage(payload);
            });

            channel.listen('.RoomTopicUpdated', (payload) => {
                this.onRoomTopicUpdatedWs(payload);
            });

            channel.listen('.RoomJournalCleared', (payload) => {
                this.onRoomJournalClearedWs(payload);
            });

            channel.listen('.PresenceStatusUpdated', (payload) => {
                if (!payload || payload.user_id == null || !payload.status) {
                    return;
                }
                const uid = String(payload.user_id);
                this.peerPresenceStatusByUserId = {
                    ...this.peerPresenceStatusByUserId,
                    [uid]: payload.status,
                };
            });

            channel.listen('.GlobalSoundPlayed', (payload) => {
                maybePlayGlobalGsound(this.user, {
                    actorUserId: payload && payload.actor_user_id,
                    chatSilentMode: Boolean(this.chatSettings && this.chatSettings.silent_mode),
                });
            });

            channel.listen('.ChatSilentModeUpdated', (payload) => {
                if (!payload || typeof payload.silent_mode === 'undefined') {
                    return;
                }
                this.chatSettings = {
                    ...(this.chatSettings || {}),
                    silent_mode: Boolean(payload.silent_mode),
                };
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
            ch.listen('.PrivateThreadCleared', (payload) => {
                this.onPrivateThreadClearedWs(payload);
            });
            ch.listen('.RoomInlinePrivatePosted', (payload) => {
                const m = normalizeMessage(payload);
                const wasNew = Boolean(m && !this.messageIds.has(m.post_id));
                this.mergeMessage(payload);
                if (wasNew) {
                    this.handleNewRoomMessageSound(m);
                }
            });
        },
        onRoomTopicUpdatedWs(payload) {
            if (!payload || payload.room_id == null) {
                return;
            }
            const rid = Number(payload.room_id);
            if (!Number.isFinite(rid)) {
                return;
            }
            const rawTopic = payload.topic;
            const topic =
                rawTopic == null || rawTopic === '' ? null : String(rawTopic);
            this.rooms = this.rooms.map((r) =>
                Number(r.room_id) === rid ? { ...r, topic } : r,
            );
        },
        onRoomJournalClearedWs(payload) {
            if (!payload || payload.room_id == null) {
                return;
            }
            const rid = Number(payload.room_id);
            if (!Number.isFinite(rid)) {
                return;
            }
            this.applyRoomJournalCleared(rid);
        },
        applyRoomJournalCleared(roomId) {
            const rid = Number(roomId);
            if (!Number.isFinite(rid) || Number(this.selectedRoomId) !== rid) {
                return;
            }
            const nextMessages = [];
            const nextIds = new Set();
            for (const m of this.messages) {
                if (m && m.type === 'public') {
                    continue;
                }
                nextMessages.push(m);
                if (m && m.post_id != null) {
                    nextIds.add(m.post_id);
                }
            }
            this.messages = nextMessages;
            this.messageIds = nextIds;
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
            const t = { ...target };
            if (t.id != null) {
                t.friendship = this.friendshipStateForUserId(t.id);
            }
            this.badgeMenu = {
                mode: 'other',
                target: t,
                rowKey,
                returnFocusEl: el,
            };
        },
        friendshipStateForUserId(id) {
            if (id == null) {
                return null;
            }
            const n = Number(id);
            if (this.friendsAccepted.some((f) => Number(f.user.id) === n)) {
                return 'accepted';
            }
            if (this.friendsIncoming.some((r) => Number(r.user.id) === n)) {
                return 'pending_in';
            }
            if (this.friendsOutgoing.some((r) => Number(r.user.id) === n)) {
                return 'pending_out';
            }

            return null;
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
            if (!m || m.post_id == null || !this.selectedRoomId || !this.selectedRoomApiSegment) {
                return;
            }
            const postId = m.post_id;
            await this.ensureSanctum();
            try {
                const { data } = await window.axios.delete(
                    `/api/v1/rooms/${this.selectedRoomApiSegment}/messages/${postId}`,
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
                if (!this.user || this.user.chat_role !== 'admin') {
                    return;
                }
                this.commandsHelpOpen = true;

                return;
            }
            if (id === 'admin-hub') {
                this.$router
                    .push({ name: 'admin-hub', query: staffContextQuery(this.rooms, this.selectedRoomId) })
                    .catch(() => {});

                return;
            }
            if (id === 'settings') {
                this.chatSettingsModalOpen = true;

                return;
            }
            if (id === 'staff-users') {
                this.$router
                    .push({ name: 'staff-users', query: staffContextQuery(this.rooms, this.selectedRoomId) })
                    .catch(() => {});

                return;
            }
            if (id === 'staff-stop-words') {
                this.$router
                    .push({
                        name: 'staff-stop-words',
                        query: staffContextQuery(this.rooms, this.selectedRoomId),
                    })
                    .catch(() => {});

                return;
            }
            if (id === 'staff-flagged') {
                this.$router
                    .push({
                        name: 'staff-flagged',
                        query: staffContextQuery(this.rooms, this.selectedRoomId),
                    })
                    .catch(() => {});

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
            if (id === 'unfriend' || id === 'cancel-friend') {
                this.removeOrCancelFriendshipFromMenuTarget(bm.target);

                return;
            }
            if (id === 'accept-friend') {
                if (bm.target && bm.target.id != null) {
                    this.acceptFriend(bm.target.id);
                }

                return;
            }
            if (id === 'reject-friend') {
                if (bm.target && bm.target.id != null) {
                    this.rejectFriend(bm.target.id);
                }

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
                this.pruneIgnoredMessagesFromFeed();
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
                this.sidebarTab = 'friends';
                this.friendsSubTab = 'pending';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося надіслати запит у друзі.';
            }
        },
        async removeOrCancelFriendshipFromMenuTarget(t) {
            if (!t || t.id == null) {
                return;
            }
            const wasOutgoingPending = this.friendshipStateForUserId(t.id) === 'pending_out';
            await this.ensureSanctum();
            try {
                await window.axios.delete(`/api/v1/friends/${t.id}`);
                this.loadError = '';
                await this.loadFriendsAndIgnores();
                this.sidebarTab = 'friends';
                this.friendsSubTab = wasOutgoingPending ? 'pending' : 'active';
            } catch (e) {
                this.loadError =
                    e.response?.data?.message || 'Не вдалося оновити список друзів.';
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
        async applyRoomSelection() {
            this.teardownEcho(false);
            this.clearMessages();
            this.loadError = '';
            await this.loadMessages();
            await this.setupEcho();
            this.startPollIfDegraded();
        },
        async selectRoom(roomId) {
            if (!roomId || roomId === this.selectedRoomId) {
                return;
            }
            this.selectedRoomId = roomId;
            this.$router
                .replace(buildChatRoute(this.rooms, roomId, this.preserveSidebarRouteQuery()))
                .catch(() => {});
            await this.applyRoomSelection();
            if (this.isNarrowViewport && this.panelOpen) {
                const mainCol = this.$refs.chatMainColumn;
                const header = mainCol && mainCol.$refs.chatRoomHeader;
                const mobileToggle = header && header.$refs.mobilePanelToggle;
                const main =
                    this.$el && typeof this.$el.querySelector === 'function'
                        ? this.$el.querySelector('#main-content')
                        : null;
                this.panelFocusReturnEl = mobileToggle || main || this.panelFocusReturnEl;
                this.closePanel();
            }
        },
        async sendMessage() {
            const comp = this.$refs.chatComposer;
            if (!comp || typeof comp.getSendPayload !== 'function') {
                return;
            }
            const { text, imageId, stylePayload, editPostId, editHadFile } = comp.getSendPayload();
            if (!this.selectedRoomId || !this.selectedRoomApiSegment || this.sending) {
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
            let focusComposerAfterSend = false;
            let pendingClientMessageId = null;
            try {
                if (editPostId != null) {
                    const patchBody = { message: text };
                    patchBody.style = stylePayload || {
                        bold: false,
                        italic: false,
                        underline: false,
                    };
                    const { data, status } = await window.axios.patch(
                        `/api/v1/rooms/${this.selectedRoomApiSegment}/messages/${editPostId}`,
                        patchBody,
                    );
                    if (data.data) {
                        this.mergeMessage(data.data);
                    }
                    if (status === 200) {
                        comp.resetAfterSend();
                        focusComposerAfterSend = true;
                    }
                } else {
                    const clientMessageId = crypto.randomUUID();
                    pendingClientMessageId = clientMessageId;
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
                    this.optimisticPostSeq -= 1;
                    const optimistic = this.buildOptimisticPublicMessage({
                        text,
                        stylePayload,
                        clientMessageId,
                        imageId,
                        tempPostId: this.optimisticPostSeq,
                    });
                    if (optimistic) {
                        this.messages.push(optimistic);
                        this.messages.sort((a, b) => Number(a.post_id) - Number(b.post_id));
                        this.$nextTick(() => this.scrollToBottom());
                    }
                    const postUrl = `/api/v1/rooms/${this.selectedRoomApiSegment}/messages`;
                    const { data, status } = await postWithOneNetworkRetry(
                        () => window.axios.post(postUrl, body),
                    );
                    logRudaPandaLlmDebugFromApiResponse(data);
                    if (data && data.data) {
                        this.mergeMessage(data.data);
                    } else if (optimistic) {
                        this.removePendingRoomMessageByClientId(clientMessageId);
                    }
                    if (status === 201 || status === 200) {
                        comp.resetAfterSend();
                        focusComposerAfterSend = true;
                        const slashName = data.meta && data.meta.slash && data.meta.slash.name;
                        if (slashName === 'ignore' || slashName === 'ignoreclear') {
                            await this.loadFriendsAndIgnores();
                            if (slashName === 'ignoreclear') {
                                await this.loadMessages();
                            } else {
                                this.pruneIgnoredMessagesFromFeed();
                            }
                        }
                        if (slashName === 'clear') {
                            this.applyRoomJournalCleared(this.selectedRoomId);
                        }
                        if (data.data && data.data.type === 'public') {
                            await this.refreshAuthUser();
                        }
                        const sm = data.meta && data.meta.slash;
                        if (sm && sm.reconnect_echo) {
                            const rid = this.selectedRoomId;
                            this.teardownEcho(false);
                            this.$nextTick(() => {
                                if (this.selectedRoomId === rid) {
                                    void this.setupEcho();
                                }
                            });
                        }
                        if (sm && sm.reload_chat_settings) {
                            await this.loadChatSettings();
                        }
                    }
                }
            } catch (e) {
                this.removePendingRoomMessageByClientId(pendingClientMessageId);
                const st = e.response && e.response.status;
                const msg = e.response?.data?.message || 'Не вдалося надіслати.';
                if (st === 429) {
                    showError(msg);
                } else {
                    this.loadError = msg;
                }
            } finally {
                this.sending = false;
                this.$nextTick(() => {
                    const c = this.$refs.chatComposer;
                    if (c && typeof c.syncComposerInputHeight === 'function') {
                        c.syncComposerInputHeight();
                    }
                    if (focusComposerAfterSend && c && typeof c.focusComposerAfterSend === 'function') {
                        c.focusComposerAfterSend();
                    }
                });
            }
        },
    },
};
</script>
