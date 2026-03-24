<template>
    <div
        id="chat-panel-private"
        role="tabpanel"
        :aria-labelledby="panelTabLabelledby('private')"
        tabindex="-1"
        :aria-hidden="active ? 'false' : 'true'"
    >
        <p v-if="conversations.length === 0" class="py-6 text-center text-[var(--rp-chat-sidebar-muted)]">
            Немає нових повідомлень
        </p>
        <ul v-else class="space-y-2">
            <li v-for="row in privateConversationRows" :key="row.key">
                <div
                    v-if="row.menuPeer"
                    class="rp-chat-side-room-btn flex w-full items-stretch gap-2 rounded-md border-2 px-3 py-2"
                >
                    <div class="flex shrink-0 items-start pt-0.5">
                        <UserAvatar :name="row.c.peer.user_name" variant="sidebar" decorative />
                    </div>
                    <button type="button" class="rp-focusable min-w-0 flex-1 text-left" @click="$emit('open-private-peer', row.c.peer)">
                        <span class="flex flex-wrap items-center gap-1.5">
                            <span class="font-semibold text-[var(--rp-chat-sidebar-fg)]">{{ row.c.peer.user_name }}</span>
                            <span
                                v-if="row.c.unread_count > 0"
                                class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white"
                                aria-hidden="true"
                                >{{ formatPrivateUnread(row.c.unread_count) }}</span
                            >
                        </span>
                        <span class="mt-0.5 block truncate text-xs text-[var(--rp-chat-sidebar-muted)]">{{
                            (row.c.last_message && row.c.last_message.body) || '—'
                        }}</span>
                    </button>
                </div>
                <p
                    v-else
                    class="rounded-md border border-dashed border-[var(--rp-chat-sidebar-border)] px-2 py-2 text-xs text-[var(--rp-chat-sidebar-muted)]"
                >
                    Некоректний запис розмови
                </p>
            </li>
        </ul>
    </div>
</template>

<script>
import UserAvatar from '../../../UserAvatar.vue';
import { formatPrivateUnread } from '../chatSidebarPresence';

export default {
    name: 'ChatSidebarPrivatePanel',
    components: { UserAvatar },
    props: {
        active: { type: Boolean, default: false },
        isNarrowViewport: { type: Boolean, default: false },
        conversations: { type: Array, default: () => [] },
        privateConversationRows: { type: Array, default: () => [] },
    },
    methods: {
        formatPrivateUnread,
        panelTabLabelledby(panelKey) {
            return (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + panelKey;
        },
    },
};
</script>
