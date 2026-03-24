<template>
    <div
        id="chat-panel-friends"
        role="tabpanel"
        :aria-labelledby="panelTabLabelledby('friends')"
        tabindex="-1"
        :aria-hidden="active ? 'false' : 'true'"
    >
        <div class="mb-3 flex gap-1">
            <button
                type="button"
                class="rp-focusable rp-tab flex-1 px-1 text-xs sm:text-sm"
                :aria-selected="friendsSubTab === 'active' ? 'true' : 'false'"
                @click="$emit('update:friendsSubTab', 'active')"
            >
                Мої друзі
            </button>
            <button
                type="button"
                class="rp-focusable rp-tab flex-1 px-1 text-xs sm:text-sm"
                :aria-selected="friendsSubTab === 'pending' ? 'true' : 'false'"
                @click="$emit('update:friendsSubTab', 'pending')"
            >
                Запити на дружбу
            </button>
        </div>
        <template v-if="friendsSubTab === 'active'">
            <p v-if="friendsAccepted.length === 0" class="text-center text-[var(--rp-chat-sidebar-muted)]">
                Список друзів порожній.<br />
                <span class="mt-2 inline-block text-xs text-[var(--rp-chat-sidebar-muted)]">
                    Додайте користувачів через меню «⋯» у списку «Люди».
                </span>
            </p>
            <ul v-else class="space-y-2">
                <li
                    v-for="f in friendsAcceptedDisplayRows"
                    :key="f.user.id"
                    class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
                    :class="f.presenceRowClass"
                >
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex min-w-0 flex-1 items-center gap-1">
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <UserAvatar :name="f.user.user_name" variant="sidebar" decorative />
                                <span
                                    class="h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                                    :class="f.presenceDotClass"
                                    role="img"
                                    :aria-label="'Статус ' + f.user.user_name + ': ' + f.presenceStatusLabel"
                                    :title="f.presenceStatusLabel"
                                />
                                <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                    f.user.user_name
                                }}</span>
                            </div>
                            <SidebarHamburgerTrigger
                                :expanded="isBadgeMenuOpen('friend-' + f.user.id)"
                                :aria-label="'Меню дій для ' + f.user.user_name"
                                @activate="$emit('open-peer-badge-menu', $event, f.menuPeer, 'friend-' + f.user.id)"
                            />
                        </div>
                        <RpButton variant="ghost" class="shrink-0 text-sm" @click="$emit('open-private-peer', f.user)">
                            Приват
                        </RpButton>
                    </div>
                    <UserBadgeInlineActionPanel
                        v-if="isBadgeMenuOpen('friend-' + f.user.id) && user"
                        :mode="badgeMenu.mode"
                        :viewer="user"
                        :target="badgeMenu.target"
                        @pick="$emit('sidebar-badge-pick', $event)"
                        @close="$emit('sidebar-badge-close')"
                    />
                </li>
            </ul>
        </template>
        <template v-else>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">Вхідні</p>
            <p
                v-if="friendsIncoming.length === 0"
                class="mb-4 text-center text-sm text-[var(--rp-chat-sidebar-muted)]"
            >
                Немає запитів у друзі
            </p>
            <ul v-else class="mb-4 space-y-2">
                <li
                    v-for="r in friendsIncomingWithMenuPeer"
                    :key="'in-' + r.user.id"
                    class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex min-w-0 flex-1 basis-full items-center gap-1 sm:basis-auto">
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <UserAvatar :name="r.user.user_name" variant="sidebar" decorative />
                                <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                    r.user.user_name
                                }}</span>
                            </div>
                            <SidebarHamburgerTrigger
                                :expanded="isBadgeMenuOpen('fin-' + r.user.id)"
                                :aria-label="'Меню дій для ' + r.user.user_name"
                                @activate="$emit('open-peer-badge-menu', $event, r.menuPeer, 'fin-' + r.user.id)"
                            />
                        </div>
                        <RpButton class="shrink-0 text-xs" @click="$emit('accept-friend', r.user.id)"> Прийняти </RpButton>
                        <RpButton variant="ghost" class="shrink-0 text-xs" @click="$emit('reject-friend', r.user.id)">
                            Відхилити
                        </RpButton>
                    </div>
                    <UserBadgeInlineActionPanel
                        v-if="isBadgeMenuOpen('fin-' + r.user.id) && user"
                        :mode="badgeMenu.mode"
                        :viewer="user"
                        :target="badgeMenu.target"
                        @pick="$emit('sidebar-badge-pick', $event)"
                        @close="$emit('sidebar-badge-close')"
                    />
                </li>
            </ul>
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">Вихідні</p>
            <p v-if="friendsOutgoing.length === 0" class="text-center text-sm text-[var(--rp-chat-sidebar-muted)]">
                Немає відправлених запитів
            </p>
            <ul v-else class="space-y-1">
                <li
                    v-for="r in friendsOutgoingWithMenuPeer"
                    :key="'out-' + r.user.id"
                    class="text-sm text-[var(--rp-chat-sidebar-fg)]"
                >
                    <div class="flex flex-col gap-2 rounded-md py-1">
                        <div class="flex items-center gap-1">
                            <div class="flex min-w-0 flex-1 items-center gap-2">
                                <UserAvatar :name="r.user.user_name" variant="sidebar" decorative />
                                <span class="truncate">{{ r.user.user_name }}</span>
                            </div>
                            <SidebarHamburgerTrigger
                                :expanded="isBadgeMenuOpen('fout-' + r.user.id)"
                                :aria-label="'Меню дій для ' + r.user.user_name"
                                @activate="$emit('open-peer-badge-menu', $event, r.menuPeer, 'fout-' + r.user.id)"
                            />
                        </div>
                        <UserBadgeInlineActionPanel
                            v-if="isBadgeMenuOpen('fout-' + r.user.id) && user"
                            :mode="badgeMenu.mode"
                            :viewer="user"
                            :target="badgeMenu.target"
                            @pick="$emit('sidebar-badge-pick', $event)"
                            @close="$emit('sidebar-badge-close')"
                        />
                    </div>
                </li>
            </ul>
        </template>
    </div>
</template>

<script>
import UserAvatar from '../../../UserAvatar.vue';
import RpButton from '../../../ui/RpButton.vue';
import UserBadgeInlineActionPanel from '../../../UserBadgeInlineActionPanel.vue';
import SidebarHamburgerTrigger from '../../../SidebarHamburgerTrigger.vue';
import {
    normalizedPresenceStatus,
    presenceRowClass,
    presenceDotClass,
    presenceLabelUa,
} from '../chatSidebarPresence';

export default {
    name: 'ChatSidebarFriendsPanel',
    components: {
        UserAvatar,
        RpButton,
        UserBadgeInlineActionPanel,
        SidebarHamburgerTrigger,
    },
    props: {
        active: { type: Boolean, default: false },
        isNarrowViewport: { type: Boolean, default: false },
        user: { type: Object, default: null },
        badgeMenu: { type: Object, default: null },
        isBadgeMenuOpen: { type: Function, required: true },
        friendsSubTab: { type: String, required: true },
        friendsAccepted: { type: Array, default: () => [] },
        friendsAcceptedWithMenuPeer: { type: Array, default: () => [] },
        friendsIncoming: { type: Array, default: () => [] },
        friendsIncomingWithMenuPeer: { type: Array, default: () => [] },
        friendsOutgoing: { type: Array, default: () => [] },
        friendsOutgoingWithMenuPeer: { type: Array, default: () => [] },
        peerPresenceStatusByUserId: { type: Object, default: () => ({}) },
    },
    computed: {
        friendsAcceptedDisplayRows() {
            const list = this.friendsAcceptedWithMenuPeer || [];

            return list.map((f) => {
                const st = this.friendListPresenceStatus(f.user);

                return {
                    ...f,
                    presenceRowClass: presenceRowClass(st),
                    presenceDotClass: presenceDotClass(st),
                    presenceStatusLabel: presenceLabelUa(st),
                };
            });
        },
    },
    methods: {
        panelTabLabelledby(panelKey) {
            return (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + panelKey;
        },
        friendListPresenceStatus(u) {
            if (!u || u.id == null) {
                return 'inactive';
            }
            const raw = this.peerPresenceStatusByUserId[String(u.id)];

            return raw === undefined || raw === null ? 'inactive' : normalizedPresenceStatus(raw);
        },
    },
};
</script>

<style scoped>
.rp-presence-row--away,
.rp-presence-row--inactive {
    filter: grayscale(1);
}

.rp-presence-row--inactive {
    opacity: 0.9;
}
</style>
