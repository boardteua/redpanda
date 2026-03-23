<template>
    <li
        class="flex gap-2 px-2 py-1.5 text-[0.9375rem] leading-snug sm:px-3 sm:py-2"
        :class="rowClassList"
        :data-rp-post-id="message.post_id"
    >
        <button
            v-if="viewerName && !isDeleted && message.post_user !== viewerName"
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
        <div class="min-w-0 flex-1 flex flex-col gap-0.5">
            <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-0.5">
                <div class="min-w-0 flex-1 leading-snug text-[var(--rp-text)]">
                    <button
                        v-if="viewerName && !isDeleted"
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
                        v-if="isDeleted"
                        class="inline-block italic text-[var(--rp-text-muted)]"
                    >
                        Повідомлення видалено
                    </span>
                    <span
                        v-else-if="isClientOnly && message.post_message"
                        class="block max-w-full whitespace-pre-wrap break-words font-mono text-[0.875rem] text-[var(--rp-text-muted)]"
                    >
                        <span class="select-none" aria-hidden="true">&gt; </span>{{ message.post_message }}
                    </span>
                    <ChatMessageBody
                        v-else-if="message.post_message && !postHasBlockMedia"
                        :class="messageBodyRootClass"
                        :body-class="bodyClassList"
                        :text="message.post_message"
                        variant="feed"
                    />
                </div>
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
                    <button
                        v-if="message.can_delete"
                        type="button"
                        class="rp-focusable rounded p-1 text-[var(--rp-text-muted)] hover:text-[var(--rp-error)]"
                        title="Видалити"
                        aria-label="Видалити повідомлення"
                        @click.stop="$emit('delete', message)"
                    >
                        <svg class="h-4 w-4" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"
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
            <ChatMessageBody
                v-if="!isDeleted && !isClientOnly && message.post_message && postHasBlockMedia"
                :class="messageBodyRootClassBlockMedia"
                :body-class="bodyClassList"
                :text="message.post_message"
                variant="feed"
            />
            <figure v-if="!isDeleted && message.image && message.image.url" class="mt-1.5">
                <button
                    type="button"
                    class="rp-focusable group max-w-full cursor-pointer rounded-md border-0 bg-transparent p-0 text-left"
                    aria-label="Збільшити вкладене зображення"
                    @click="onAttachmentLightbox($event)"
                >
                    <img
                        :src="message.image.url"
                        alt="Вкладене зображення"
                        class="pointer-events-none max-h-64 max-w-full rounded-md border border-[var(--rp-chat-chrome-border)] object-contain group-hover:opacity-95"
                        loading="lazy"
                    />
                </button>
            </figure>
        </div>
    </li>
</template>

<script>
import ChatMessageBody from './ChatMessageBody.vue';
import { openImageLightbox } from '../../utils/imageLightboxStore';
import { messageHasBlockMedia } from '../../utils/chatMessageBodyParse';
import { chatMessageBodyClassList, nickColorStyleForPost } from '../../utils/chatMessageStyle';

export default {
    name: 'ChatFeedMessageRow',
    components: { ChatMessageBody },
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
        isDeleted() {
            const t = this.message && this.message.post_deleted_at;

            return t != null && t !== '';
        },
        rowClassList() {
            const m = this.message;
            const even = this.index % 2 === 0;
            return [
                even ? 'bg-[var(--rp-chat-row-even)]' : 'bg-[var(--rp-chat-row-odd)]',
                m.type === 'inline_private' ? 'rp-chat-feed-row--inline-private' : '',
                m.type === 'client_only' ? 'rp-chat-feed-row--client-only' : '',
                this.isDeleted ? 'opacity-90' : '',
            ];
        },
        isClientOnly() {
            return this.message && this.message.type === 'client_only';
        },
        nickStyle() {
            return nickColorStyleForPost(this.message);
        },
        bodyClassList() {
            return chatMessageBodyClassList(this.message.post_style);
        },
        postHasBlockMedia() {
            if (this.isClientOnly) {
                return false;
            }
            return messageHasBlockMedia(this.message.post_message);
        },
        /** Повний рядок під ніком/часом — коректна ширина для 16:9 ембедів (не flex+baseline колонка). */
        messageBodyRootClassBlockMedia() {
            return ['rounded', 'px-0.5', 'block', 'w-full', 'max-w-full', 'min-w-0'];
        },
        messageBodyRootClass() {
            return ['rounded', 'px-0.5', 'inline-block', 'max-w-full', 'align-baseline'];
        },
    },
    methods: {
        onAttachmentLightbox(event) {
            const url = this.message && this.message.image && this.message.image.url;
            if (!url) {
                return;
            }
            const el = event && event.currentTarget;

            openImageLightbox({
                src: url,
                alt: 'Вкладене зображення',
                returnFocusEl: el instanceof HTMLElement ? el : null,
            });
        },
    },
};
</script>
