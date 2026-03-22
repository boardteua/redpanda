<template>
    <div class="rp-chat-feed-wash flex min-h-0 flex-1 flex-col overflow-hidden">
        <ul
            ref="scrollContainer"
            class="flex min-h-0 flex-1 flex-col overflow-y-auto py-1"
            role="log"
            aria-live="polite"
            aria-relevant="additions"
        >
            <ChatFeedMessageRow
                v-for="(m, msgIdx) in messages"
                :key="m.post_id"
                :message="m"
                :index="msgIdx"
                :viewer-name="viewerName"
                @inline-private="$emit('inline-private', $event)"
                @mention="$emit('mention', $event)"
            />
        </ul>
        <p
            v-if="messages.length === 0 && !loadingMessages"
            class="p-4 text-center text-sm text-[var(--rp-text-muted)]"
        >
            Ще немає повідомлень. Напишіть перше нижче.
        </p>
    </div>
</template>

<script>
import ChatFeedMessageRow from './ChatFeedMessageRow.vue';

export default {
    name: 'ChatFeedMessageList',
    components: { ChatFeedMessageRow },
    props: {
        messages: { type: Array, required: true },
        loadingMessages: { type: Boolean, default: false },
        viewerName: { type: String, default: '' },
    },
    methods: {
        scrollToBottom() {
            const el = this.$refs.scrollContainer;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
    },
};
</script>
