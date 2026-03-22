<template>
    <li
        class="flex gap-2 px-2 py-1.5 text-[0.9375rem] leading-snug sm:px-3 sm:py-2"
        :class="rowClassList"
    >
        <button
            v-if="viewerName && message.post_user !== viewerName"
            type="button"
            class="rp-focusable h-fit shrink-0 rounded-full border-0 bg-transparent p-0"
            :aria-label="'Приват у полі вводу: ' + message.post_user"
            @click.stop="$emit('inline-private', message.post_user)"
        >
            <UserAvatar
                :src="message.avatar"
                :name="message.post_user"
                variant="feed"
                decorative
            />
        </button>
        <UserAvatar
            v-else
            :src="message.avatar"
            :name="message.post_user"
            variant="feed"
            decorative
        />
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                <p class="min-w-0 flex-1 leading-snug text-[var(--rp-text)]">
                    <button
                        v-if="viewerName"
                        type="button"
                        class="rp-focusable mr-1.5 inline font-semibold hover:underline"
                        :style="nickStyle"
                        :aria-label="'Згадати у полі вводу: ' + message.post_user"
                        @click.stop="$emit('mention', message.post_user)"
                    >
                        {{ message.post_user }}
                    </button>
                    <span
                        v-else
                        class="mr-1.5 inline font-semibold"
                        :style="nickStyle"
                    >
                        {{ message.post_user }}
                    </span>
                    <span
                        v-if="message.post_message"
                        class="inline-block whitespace-pre-wrap break-words rounded px-0.5"
                        :class="bodyClassList"
                    >
                        {{ message.post_message }}
                    </span>
                </p>
                <time
                    class="shrink-0 font-mono text-[0.6875rem] tabular-nums text-[var(--rp-text-muted)]"
                >
                    {{ message.post_time || '—' }}
                </time>
            </div>
            <figure v-if="message.image && message.image.url" class="mt-1.5">
                <img
                    :src="message.image.url"
                    alt="Вкладене зображення"
                    class="max-h-64 max-w-full rounded-md border border-[var(--rp-chat-chrome-border)] object-contain"
                    loading="lazy"
                />
            </figure>
        </div>
    </li>
</template>

<script>
import { chatMessageBodyClassList, nickColorStyleForPost } from '../../utils/chatMessageStyle';

export default {
    name: 'ChatFeedMessageRow',
    props: {
        message: {
            type: Object,
            required: true,
        },
        index: {
            type: Number,
            required: true,
        },
        /** `user.user_name` коли залогінений; порожній — режим гостя (без кліків по ніку/аватарці). */
        viewerName: {
            type: String,
            default: '',
        },
    },
    computed: {
        rowClassList() {
            const m = this.message;
            const even = this.index % 2 === 0;
            return [
                even ? 'bg-[var(--rp-chat-row-even)]' : 'bg-[var(--rp-chat-row-odd)]',
                m.type === 'inline_private' ? 'rp-chat-feed-row--inline-private' : '',
            ];
        },
        nickStyle() {
            return nickColorStyleForPost(this.message);
        },
        bodyClassList() {
            return chatMessageBodyClassList(this.message.post_style);
        },
    },
};
</script>
