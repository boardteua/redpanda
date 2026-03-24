<template>
    <aside
        id="chat-panel"
        class="rp-chat-sidebar rp-chat-burger-drawer flex w-[320px] max-w-[100vw] flex-shrink-0 flex-col border-l border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-bg)] text-[var(--rp-chat-sidebar-fg)] max-md:fixed max-md:inset-y-0 max-md:right-0 max-md:z-50 max-md:shadow-2xl max-md:transition-transform max-md:duration-200 max-md:ease-out md:relative md:z-auto md:min-h-0 md:self-stretch md:max-w-[320px] md:shadow-none md:transition-none"
        :class="[
            isNarrowViewport && (panelOpen ? 'max-md:translate-x-0' : 'max-md:translate-x-full'),
            !isNarrowViewport && !panelOpen ? 'md:hidden' : '',
        ]"
        aria-label="Панель чату"
    >
        <ChatSidebarTabBars
            ref="sidebarTabBars"
            :sidebar-tabs="sidebarTabs"
            :sidebar-tab="sidebarTab"
            :private-unread-total="privateUnreadTotal"
            @close="$emit('close')"
            @select-tab="$emit('select-tab', $event)"
        />

        <div
            class="rp-chat-burger-scroll min-h-0 flex-1 overflow-y-auto p-3 text-sm text-[var(--rp-chat-sidebar-fg)]"
        >
            <div
                v-if="privateListLoadError || friendsIgnoresLoadError"
                class="mb-3 space-y-2"
                role="region"
                aria-label="Помилки завантаження списків"
            >
                <p
                    v-if="privateListLoadError"
                    role="alert"
                    class="rounded-md border border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-xs text-[var(--rp-error)]"
                >
                    {{ privateListLoadError }}
                </p>
                <p
                    v-if="friendsIgnoresLoadError"
                    role="alert"
                    class="rounded-md border border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-xs text-[var(--rp-error)]"
                >
                    {{ friendsIgnoresLoadError }}
                </p>
            </div>

            <ChatSidebarUsersPanel
                v-show="sidebarTab === 'users'"
                :active="sidebarTab === 'users'"
                :is-narrow-viewport="isNarrowViewport"
                :user="user"
                :badge-menu="badgeMenu"
                :is-badge-menu-open="isBadgeMenuOpen"
                :room-presence-peers="roomPresencePeers"
                :peer-presence-status-by-user-id="peerPresenceStatusByUserId"
                :peer-sex-hints-by-user-id="peerSexHintsByUserId"
                :viewer-presence-status="viewerPresenceStatus"
                :ws-degraded="wsDegraded"
                :peer-lookup-name="peerLookupName"
                :peer-autocomplete-suggestions="peerAutocompleteSuggestions"
                :peer-autocomplete-highlight-index="peerAutocompleteHighlightIndex"
                :peer-autocomplete-loading="peerAutocompleteLoading"
                :peer-autocomplete-open="peerAutocompleteOpen"
                @open-self-badge-menu="(...a) => $emit('open-self-badge-menu', ...a)"
                @sidebar-badge-pick="(...a) => $emit('sidebar-badge-pick', ...a)"
                @sidebar-badge-close="(...a) => $emit('sidebar-badge-close', ...a)"
                @open-peer-badge-menu="(...a) => $emit('open-peer-badge-menu', ...a)"
                @pick-peer-autocomplete="(...a) => $emit('pick-peer-autocomplete', ...a)"
                @lookup-private="(...a) => $emit('lookup-private', ...a)"
                @update:peerLookupName="(...a) => $emit('update:peerLookupName', ...a)"
                @peer-lookup-keydown="(...a) => $emit('peer-lookup-keydown', ...a)"
            />

            <ChatSidebarFriendsPanel
                v-show="sidebarTab === 'friends'"
                :active="sidebarTab === 'friends'"
                :is-narrow-viewport="isNarrowViewport"
                :user="user"
                :badge-menu="badgeMenu"
                :is-badge-menu-open="isBadgeMenuOpen"
                :friends-sub-tab="friendsSubTab"
                :friends-accepted="friendsAccepted"
                :friends-accepted-with-menu-peer="friendsAcceptedWithMenuPeer"
                :friends-incoming="friendsIncoming"
                :friends-incoming-with-menu-peer="friendsIncomingWithMenuPeer"
                :friends-outgoing="friendsOutgoing"
                :friends-outgoing-with-menu-peer="friendsOutgoingWithMenuPeer"
                :peer-presence-status-by-user-id="peerPresenceStatusByUserId"
                @update:friendsSubTab="(...a) => $emit('update:friendsSubTab', ...a)"
                @open-peer-badge-menu="(...a) => $emit('open-peer-badge-menu', ...a)"
                @open-private-peer="(...a) => $emit('open-private-peer', ...a)"
                @sidebar-badge-pick="(...a) => $emit('sidebar-badge-pick', ...a)"
                @sidebar-badge-close="(...a) => $emit('sidebar-badge-close', ...a)"
                @accept-friend="(...a) => $emit('accept-friend', ...a)"
                @reject-friend="(...a) => $emit('reject-friend', ...a)"
            />

            <ChatSidebarPrivatePanel
                v-show="sidebarTab === 'private'"
                :active="sidebarTab === 'private'"
                :is-narrow-viewport="isNarrowViewport"
                :conversations="conversations"
                :private-conversation-rows="privateConversationRows"
                @open-private-peer="(...a) => $emit('open-private-peer', ...a)"
            />

            <ChatSidebarRoomsPanel
                v-show="sidebarTab === 'rooms'"
                :active="sidebarTab === 'rooms'"
                :is-narrow-viewport="isNarrowViewport"
                :user="user"
                :rooms="rooms"
                :loading-rooms="loadingRooms"
                :selected-room-id="selectedRoomId"
                @open-add-room="(...a) => $emit('open-add-room', ...a)"
                @select-room="(...a) => $emit('select-room', ...a)"
                @edit-room="(...a) => $emit('edit-room', ...a)"
            />

            <ChatSidebarIgnorePanel
                v-show="sidebarTab === 'ignore'"
                :active="sidebarTab === 'ignore'"
                :is-narrow-viewport="isNarrowViewport"
                :user="user"
                :badge-menu="badgeMenu"
                :is-badge-menu-open="isBadgeMenuOpen"
                :ignores="ignores"
                :ignores-with-menu-peer="ignoresWithMenuPeer"
                @open-peer-badge-menu="(...a) => $emit('open-peer-badge-menu', ...a)"
                @remove-ignore="(...a) => $emit('remove-ignore', ...a)"
                @sidebar-badge-pick="(...a) => $emit('sidebar-badge-pick', ...a)"
                @sidebar-badge-close="(...a) => $emit('sidebar-badge-close', ...a)"
            />
        </div>
    </aside>
</template>

<script>
import ChatSidebarTabBars from './ChatSidebarTabBars.vue';
import ChatSidebarUsersPanel from './tabs/ChatSidebarUsersPanel.vue';
import ChatSidebarFriendsPanel from './tabs/ChatSidebarFriendsPanel.vue';
import ChatSidebarPrivatePanel from './tabs/ChatSidebarPrivatePanel.vue';
import ChatSidebarRoomsPanel from './tabs/ChatSidebarRoomsPanel.vue';
import ChatSidebarIgnorePanel from './tabs/ChatSidebarIgnorePanel.vue';

export default {
    name: 'ChatRoomSidebar',
    components: {
        ChatSidebarTabBars,
        ChatSidebarUsersPanel,
        ChatSidebarFriendsPanel,
        ChatSidebarPrivatePanel,
        ChatSidebarRoomsPanel,
        ChatSidebarIgnorePanel,
    },
    props: {
        panelOpen: { type: Boolean, default: false },
        isNarrowViewport: { type: Boolean, default: false },
        sidebarTabs: { type: Array, required: true },
        sidebarTab: { type: String, required: true },
        privateListLoadError: { type: String, default: '' },
        friendsIgnoresLoadError: { type: String, default: '' },
        user: { type: Object, default: null },
        badgeMenu: { type: Object, default: null },
        isBadgeMenuOpen: { type: Function, required: true },
        roomPresencePeers: { type: Array, default: () => [] },
        peerPresenceStatusByUserId: { type: Object, default: () => ({}) },
        peerSexHintsByUserId: { type: Object, default: () => ({}) },
        viewerPresenceStatus: { type: String, default: 'online' },
        wsDegraded: { type: Boolean, default: false },
        peerLookupName: { type: String, default: '' },
        /** T85 */
        peerAutocompleteSuggestions: { type: Array, default: () => [] },
        peerAutocompleteHighlightIndex: { type: Number, default: -1 },
        peerAutocompleteLoading: { type: Boolean, default: false },
        peerAutocompleteOpen: { type: Boolean, default: false },
        friendsSubTab: { type: String, required: true },
        friendsAccepted: { type: Array, default: () => [] },
        friendsAcceptedWithMenuPeer: { type: Array, default: () => [] },
        friendsIncoming: { type: Array, default: () => [] },
        friendsIncomingWithMenuPeer: { type: Array, default: () => [] },
        friendsOutgoing: { type: Array, default: () => [] },
        friendsOutgoingWithMenuPeer: { type: Array, default: () => [] },
        conversations: { type: Array, default: () => [] },
        privateConversationRows: { type: Array, default: () => [] },
        /** T56: сума непрочитаних вхідних приватних для вкладки «Приват». */
        privateUnreadTotal: { type: Number, default: 0 },
        rooms: { type: Array, default: () => [] },
        loadingRooms: { type: Boolean, default: false },
        selectedRoomId: {
            default: null,
            validator: (v) => v === null || v === undefined || typeof v === 'number',
        },
        ignores: { type: Array, default: () => [] },
        ignoresWithMenuPeer: { type: Array, default: () => [] },
    },
};
</script>
