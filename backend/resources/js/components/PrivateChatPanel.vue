<template>
    <div
        v-if="peer"
        class="fixed bottom-4 left-4 right-4 z-[60] flex max-h-[min(70vh,28rem)] flex-col rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] shadow-xl md:left-auto md:right-4 md:w-[min(100vw-2rem,22rem)]"
        role="dialog"
        aria-modal="true"
        :aria-label="'Приват: ' + peer.user_name"
    >
        <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-3 py-2">
            <span class="flex min-w-0 items-center gap-2">
                <UserAvatar variant="private" :name="peer.user_name" decorative />
                <span class="truncate font-semibold text-[var(--rp-text)]">{{ peer.user_name }}</span>
            </span>
            <button
                type="button"
                class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)]"
                aria-label="Закрити приват"
                @click="$emit('close')"
            >
                <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                    />
                </svg>
            </button>
        </div>
        <p v-if="error" class="shrink-0 px-3 py-1 text-xs text-[var(--rp-error)]" role="alert">
            {{ error }}
        </p>
        <ul
            ref="privateList"
            class="min-h-0 flex-1 overflow-y-auto p-2 text-sm"
            role="log"
            aria-live="polite"
        >
            <li
                v-for="m in messages"
                :key="m.id"
                class="mb-2 flex gap-2 border-b border-[var(--rp-border-subtle)] pb-2 last:mb-0 last:border-0 last:pb-0"
            >
                <UserAvatar
                    variant="private"
                    :name="avatarNameFor(m)"
                    :src="avatarSrcFor(m)"
                    decorative
                />
                <div class="min-w-0 flex-1">
                    <div class="text-xs text-[var(--rp-text-muted)]">
                        <time>{{ m.sent_time || '—' }}</time>
                        <span class="ml-2 font-medium text-[var(--rp-text)]">{{ labelFor(m) }}</span>
                    </div>
                    <ChatMessageBody
                        class="mt-1 text-[var(--rp-text)]"
                        :text="m.body"
                        variant="private"
                    />
                </div>
            </li>
        </ul>
        <form class="shrink-0 border-t border-[var(--rp-border-subtle)] p-2" @submit.prevent="onSubmit">
            <label class="rp-sr-only" for="private-composer">Текст приватного повідомлення</label>
            <textarea
                id="private-composer"
                :value="composerText"
                class="rp-input rp-focusable mb-2 min-h-[4rem] resize-y font-sans"
                maxlength="4000"
                rows="2"
                :disabled="sending"
                placeholder="Повідомлення…"
                @input="$emit('update:composerText', $event.target.value)"
            />
            <RpButton native-type="submit" class="w-full" :disabled="sending || !composerText.trim()">
                Надіслати
            </RpButton>
        </form>
    </div>
</template>

<script>
import ChatMessageBody from './chat/ChatMessageBody.vue';

export default {
    name: 'PrivateChatPanel',
    components: { ChatMessageBody },
    props: {
        peer: {
            type: Object,
            default: null,
        },
        messages: {
            type: Array,
            default: () => [],
        },
        loading: {
            type: Boolean,
            default: false,
        },
        sending: {
            type: Boolean,
            default: false,
        },
        error: {
            type: String,
            default: '',
        },
        composerText: {
            type: String,
            default: '',
        },
        currentUserId: {
            type: Number,
            required: true,
        },
        currentUserName: {
            type: String,
            default: '',
        },
        currentUserAvatarUrl: {
            type: String,
            default: '',
        },
    },
    watch: {
        messages: {
            handler() {
                this.$nextTick(() => this.scrollBottom());
            },
            deep: true,
        },
        peer() {
            this.$nextTick(() => this.scrollBottom());
        },
    },
    methods: {
        labelFor(m) {
            return Number(m.sender_id) === Number(this.currentUserId) ? 'Ви' : this.peer.user_name;
        },
        avatarNameFor(m) {
            return Number(m.sender_id) === Number(this.currentUserId)
                ? this.currentUserName || 'Ви'
                : this.peer.user_name;
        },
        avatarSrcFor(m) {
            return Number(m.sender_id) === Number(this.currentUserId) ? this.currentUserAvatarUrl : '';
        },
        scrollBottom() {
            const el = this.$refs.privateList;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
        onSubmit() {
            const t = this.composerText.trim();
            if (!t || this.sending) {
                return;
            }
            this.$emit('send', t);
        },
    },
};
</script>
