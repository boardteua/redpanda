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
                <div class="flex shrink-0 items-center gap-1.5">
                    <button
                        v-if="message.can_edit"
                        type="button"
                        class="rp-focusable rounded p-1 text-[var(--rp-text-muted)] hover:text-[var(--rp-text)]"
                        title="Редагувати"
                        aria-label="Редагувати повідомлення"
                        @click.stop="$emit('edit', message)"
                    >
                        <svg class="h-4 w-4" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"
                            />
                        </svg>
                    </button>
                    <span
                        v-if="message.post_edited_at"
                        class="text-[0.625rem] font-medium uppercase tracking-wide text-[var(--rp-text-muted)]"
                    >
                        змінено
                    </span>
                    <time
                        class="font-mono text-[0.6875rem] tabular-nums text-[var(--rp-text-muted)]"
                    >
                        {{ message.post_time || '—' }}
                    </time>
                </div>
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
