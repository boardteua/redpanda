<template>
    <div
        class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden bg-[var(--rp-chat-app-bg)] px-3 py-2 md:px-0 md:py-2"
    >
        <ChatRoomHeader
            ref="chatRoomHeader"
            :chat-title="chatTitle"
            :chat-topic-line="chatTopicLine"
            :panel-open="panelOpen"
            :ws-degraded="wsDegraded"
            @toggle-panel="$emit('toggle-panel')"
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
                v-else-if="roomsEmpty"
                class="rp-banner shrink-0"
                role="status"
            >
                Немає доступних кімнат. Зверніться до адміністратора.
            </div>

            <div
                v-else
                class="flex min-h-0 flex-1 flex-col overflow-hidden border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-feed-bg)] md:border-0 md:shadow-none"
            >
                <slot />
            </div>
        </main>
    </div>
</template>

<script>
import ChatRoomHeader from './ChatRoomHeader.vue';

export default {
    name: 'ChatRoomMainColumn',
    components: { ChatRoomHeader },
    props: {
        /** Панель сайдбару відкрита — для кнопки «бургер» у шапці. */
        panelOpen: { type: Boolean, default: false },
        chatTitle: { type: String, default: '' },
        chatTopicLine: { type: String, default: '' },
        wsDegraded: { type: Boolean, default: false },
        logoutError: { type: String, default: '' },
        loadError: { type: String, default: '' },
        roomsEmpty: { type: Boolean, default: false },
    },
};
</script>
