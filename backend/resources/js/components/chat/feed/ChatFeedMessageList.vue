<template>
    <div class="rp-chat-feed-wash flex min-h-0 flex-1 flex-col overflow-hidden">
        <ul
            ref="scrollContainer"
            class="flex min-h-0 flex-1 flex-col overflow-y-auto"
            role="log"
            aria-live="polite"
            aria-relevant="additions"
        >
            <template v-for="item in feedItems">
                <li
                    v-if="item.kind === 'divider'"
                    :key="item.key"
                    class="mx-2 my-2 flex list-none items-center gap-2 py-0.5"
                    role="separator"
                    aria-orientation="horizontal"
                >
                    <span class="h-px flex-1 bg-[var(--rp-chat-chrome-border)]" aria-hidden="true" />
                    <span
                        class="shrink-0 text-xs font-medium uppercase tracking-wide text-[var(--rp-text-muted)]"
                    >
                        Нові повідомлення
                    </span>
                    <span class="h-px flex-1 bg-[var(--rp-chat-chrome-border)]" aria-hidden="true" />
                </li>
                <ChatFeedMessageRow
                    v-else
                    :key="item.key"
                    :message="item.message"
                    :index="item.msgIndex"
                    :viewer-name="viewerName"
                    :current-room-id="currentRoomId"
                    @inline-private="$emit('inline-private', $event)"
                    @mention="$emit('mention', $event)"
                    @edit="$emit('edit', $event)"
                    @delete="$emit('delete', $event)"
                />
            </template>
            <li
                ref="bottomSentinel"
                class="h-2 w-full shrink-0 list-none"
                aria-hidden="true"
            />
        </ul>
        <p
            v-if="messages.length === 0 && !loadingMessages"
            class="rp-chat-feed-empty mx-auto max-w-md p-4 text-center text-sm text-[var(--rp-text-muted)]"
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
        /** Поточна відкрита кімната — щоб приховати CTA «перейти», якщо вже там (T150). */
        currentRoomId: { type: Number, default: null },
        /** Перший post_id блоку «нові» після входу в кімнату (T47). */
        dividerBeforePostId: { type: Number, default: null },
        dividerDismissed: { type: Boolean, default: false },
        /** До цього часу (epoch ms) ігноруємо «низ стрічки» для зняття розділювача (після programmatic scroll). */
        bottomDismissSuppressUntil: { type: Number, default: 0 },
        /** Змінюється при додаванні/оновленні рядків (батько: довжина + останній post_id). */
        syncKey: { type: String, default: '' },
    },
    computed: {
        feedItems() {
            const out = [];
            const divId = this.dividerBeforePostId;
            const dismissed = this.dividerDismissed;
            this.messages.forEach((m, msgIdx) => {
                if (divId != null && !dismissed && Number(m.post_id) === Number(divId)) {
                    out.push({ kind: 'divider', key: `div-${m.post_id}` });
                }
                out.push({ kind: 'msg', key: `msg-${m.post_id}`, message: m, msgIndex: msgIdx });
            });

            return out;
        },
    },
    watch: {
        syncKey() {
            this.$nextTick(() => this.setupBottomObserver());
        },
        dividerBeforePostId() {
            this.$nextTick(() => this.setupBottomObserver());
        },
        dividerDismissed() {
            this.$nextTick(() => this.setupBottomObserver());
        },
        bottomDismissSuppressUntil() {
            this.$nextTick(() => this.setupBottomObserver());
        },
    },
    mounted() {
        this.$nextTick(() => this.setupBottomObserver());
    },
    beforeDestroy() {
        this.teardownBottomObserver();
    },
    methods: {
        scrollToBottom() {
            const el = this.$refs.scrollContainer;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
        /** T60: прокрутка до повідомлення з черги модерації (`?focus_post=`). */
        scrollToPost(postId) {
            const el = this.$refs.scrollContainer;
            if (!el || postId == null) {
                return;
            }
            const sel = `[data-rp-post-id="${Number(postId)}"]`;
            const row = el.querySelector(sel);
            if (!row || typeof row.scrollIntoView !== 'function') {
                return;
            }
            row.scrollIntoView({ block: 'center', behavior: 'smooth' });
            if (typeof row.focus === 'function') {
                try {
                    row.setAttribute('tabindex', '-1');
                    row.focus({ preventScroll: true });
                } catch {
                    /* */
                }
            }
        },
        setupBottomObserver() {
            this.teardownBottomObserver();
            const root = this.$refs.scrollContainer;
            const target = this.$refs.bottomSentinel;
            if (
                !root
                || !target
                || typeof IntersectionObserver === 'undefined'
                || this.messages.length === 0
            ) {
                return;
            }
            const suppressUntil = Number(this.bottomDismissSuppressUntil) || 0;
            this._bottomObs = new IntersectionObserver(
                (entries) => {
                    const hit = entries.some((e) => e.isIntersecting);
                    if (!hit) {
                        return;
                    }
                    if (Date.now() < suppressUntil) {
                        return;
                    }
                    this.$emit('feed-bottom-visible');
                },
                { root, rootMargin: '0px 0px 48px 0px', threshold: 0 },
            );
            this._bottomObs.observe(target);
        },
        teardownBottomObserver() {
            if (this._bottomObs) {
                this._bottomObs.disconnect();
                this._bottomObs = null;
            }
        },
    },
};
</script>
