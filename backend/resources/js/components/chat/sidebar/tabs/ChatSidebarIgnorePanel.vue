<template>
    <div
        id="chat-panel-ignore"
        role="tabpanel"
        :aria-labelledby="panelTabLabelledby('ignore')"
        tabindex="-1"
        :aria-hidden="active ? 'false' : 'true'"
    >
        <p v-if="ignores.length === 0" class="py-6 text-center text-[var(--rp-chat-sidebar-muted)]">
            Список ігнор порожній
        </p>
        <ul v-else class="space-y-2">
            <li
                v-for="row in ignoresWithMenuPeer"
                :key="row.user.id"
                class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex min-w-0 flex-1 items-center gap-1">
                        <div class="flex min-w-0 flex-1 items-center gap-2">
                            <UserAvatar :name="row.user.user_name" variant="sidebar" decorative />
                            <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                row.user.user_name
                            }}</span>
                        </div>
                        <SidebarHamburgerTrigger
                            :expanded="isBadgeMenuOpen('ign-' + row.user.id)"
                            :aria-label="'Меню дій для ' + row.user.user_name"
                            @activate="$emit('open-peer-badge-menu', $event, row.menuPeer, 'ign-' + row.user.id)"
                        />
                    </div>
                    <button
                        type="button"
                        class="rp-focusable shrink-0 text-sm font-semibold text-[var(--rp-chat-sidebar-link)] hover:text-[var(--rp-chat-sidebar-link-hover)] hover:underline"
                        @click="$emit('remove-ignore', row.user.id)"
                    >
                        Зняти
                    </button>
                </div>
                <UserBadgeInlineActionPanel
                    v-if="isBadgeMenuOpen('ign-' + row.user.id) && user"
                    :mode="badgeMenu.mode"
                    :viewer="user"
                    :target="badgeMenu.target"
                    @pick="$emit('sidebar-badge-pick', $event)"
                    @close="$emit('sidebar-badge-close')"
                />
            </li>
        </ul>
    </div>
</template>

<script>
import UserAvatar from '../../../UserAvatar.vue';
import UserBadgeInlineActionPanel from '../../../UserBadgeInlineActionPanel.vue';
import SidebarHamburgerTrigger from '../../../SidebarHamburgerTrigger.vue';

export default {
    name: 'ChatSidebarIgnorePanel',
    components: {
        UserAvatar,
        UserBadgeInlineActionPanel,
        SidebarHamburgerTrigger,
    },
    props: {
        active: { type: Boolean, default: false },
        isNarrowViewport: { type: Boolean, default: false },
        user: { type: Object, default: null },
        badgeMenu: { type: Object, default: null },
        isBadgeMenuOpen: { type: Function, required: true },
        ignores: { type: Array, default: () => [] },
        ignoresWithMenuPeer: { type: Array, default: () => [] },
    },
    methods: {
        panelTabLabelledby(panelKey) {
            return (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + panelKey;
        },
    },
};
</script>
