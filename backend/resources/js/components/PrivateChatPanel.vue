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
            <RpCloseButton aria-label="Закрити приват" @click="$emit('close')" />
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
                        <time :datetime="privateMessageDatetime(m) || undefined">{{
                            privateMessageTimeLabel(m)
                        }}</time>
                        <span
                            v-if="m.rp_send_pending"
                            class="ml-1.5 italic text-[var(--rp-text-muted)]"
                        >
                            відправка…
                        </span>
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
            <p id="private-composer-keys-hint" class="rp-sr-only">
                Enter — надіслати повідомлення. Shift+Enter — новий рядок.
            </p>
            <textarea
                id="private-composer"
                ref="privateComposer"
                :value="composerText"
                class="rp-input rp-focusable mb-2 min-h-[4rem] resize-y font-sans"
                maxlength="4000"
                rows="2"
                :disabled="sending"
                :placeholder="privateComposerPlaceholder"
                aria-describedby="private-composer-keys-hint"
                @input="$emit('update:composerText', $event.target.value)"
                @keydown="onComposerKeydown"
            />
            <RpButton native-type="submit" class="w-full" :disabled="sending || !composerText.trim()">
                Надіслати
            </RpButton>
        </form>
    </div>
</template>

<script>
import ChatMessageBody from './chat/feed/ChatMessageBody.vue';
import { formatChatMessageTimeLocal, isoUtcFromUnixSeconds } from '../utils/formatChatMessageTime';

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
        /** T115: згадка slash-команд у placeholder лише для адміна чату. */
        showSlashDocs: {
            type: Boolean,
            default: false,
        },
    },
    computed: {
        privateComposerPlaceholder() {
            if (this.showSlashDocs) {
                return 'Повідомлення… (команда /clear — очистити тред)';
            }

            return 'Повідомлення — Enter надішле, Shift+Enter — новий рядок';
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
        privateMessageTimeLabel(m) {
            const formatted = formatChatMessageTimeLocal(m && m.sent_at);
            if (formatted) {
                return formatted;
            }

            return (m && m.sent_time) || '—';
        },
        privateMessageDatetime(m) {
            return isoUtcFromUnixSeconds(m && m.sent_at);
        },
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
        /** Enter — відправка; Shift+Enter — перенос рядка (як у месенджерах). */
        onComposerKeydown(e) {
            if (e.key !== 'Enter') {
                return;
            }
            if (e.shiftKey) {
                return;
            }
            if (e.isComposing || e.keyCode === 229) {
                return;
            }
            e.preventDefault();
            this.onSubmit();
        },
        /** T124: після успішної відправки та `sending=false` у батька. */
        scheduleFocusComposer() {
            window.requestAnimationFrame(() => {
                this.$nextTick(() => {
                    this.$nextTick(() => {
                        const el = this.$refs.privateComposer;
                        if (!el || typeof el.focus !== 'function') {
                            return;
                        }
                        el.focus();
                        const len = String(this.composerText || '').length;
                        try {
                            el.setSelectionRange(len, len);
                        } catch {
                            /* */
                        }
                    });
                });
            });
        },
    },
};
</script>
