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
            <li ref="topSentinel" class="h-1 w-full shrink-0 list-none" aria-hidden="true" />
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
                        v-if="m.body && !privatePostHasBlockMedia(m)"
                        class="mt-1 text-[var(--rp-text)]"
                        :text="m.body"
                        variant="private"
                    />
                    <ChatMessageBody
                        v-if="m.body && privatePostHasBlockMedia(m)"
                        class="mt-1 block w-full max-w-full min-w-0 text-[var(--rp-text)]"
                        :text="m.body"
                        variant="private"
                    />
                    <figure v-if="m.image && m.image.url" class="mt-1.5">
                        <button
                            type="button"
                            class="rp-focusable group max-w-full cursor-pointer rounded-md border-0 bg-transparent p-0 text-left"
                            aria-label="Збільшити вкладене зображення"
                            @click="onPrivateAttachmentLightbox($event, m)"
                        >
                            <img
                                :src="m.image.url"
                                alt="Вкладене зображення"
                                class="pointer-events-none max-h-64 max-w-full rounded-md border border-[var(--rp-chat-chrome-border)] object-contain group-hover:opacity-95"
                                loading="lazy"
                            />
                        </button>
                    </figure>
                </div>
            </li>
        </ul>
        <form class="shrink-0 border-t border-[var(--rp-border-subtle)] p-2" @submit.prevent="onSubmit">
            <label class="rp-sr-only" for="private-composer">Текст приватного повідомлення</label>
            <p id="private-composer-keys-hint" class="rp-sr-only">
                Enter — надішле повідомлення. Shift+Enter — новий рядок.
            </p>
            <div class="mb-2 flex items-end gap-1.5">
                <div class="min-w-0 flex-1">
                    <textarea
                        id="private-composer"
                        ref="privateComposer"
                        :value="composerText"
                        class="rp-input rp-focusable min-h-[4rem] w-full resize-y font-sans"
                        :maxlength="messageMaxLength"
                        rows="2"
                        :disabled="sending || uploadingImage"
                        :placeholder="privateComposerPlaceholder"
                        aria-describedby="private-composer-keys-hint"
                        @input="$emit('update:composerText', $event.target.value)"
                        @keydown="onComposerKeydown"
                        @paste="onChatComposerPaste"
                    />
                </div>
                <div class="flex shrink-0 flex-col gap-1">
                    <button
                        type="button"
                        class="rp-focusable rounded-md border border-[var(--rp-border-subtle)] p-2 text-[var(--rp-text)] hover:bg-[var(--rp-surface-elevated)] disabled:opacity-50"
                        :disabled="sending || uploadingImage || imageUploadBlocked || !imageUploadContextActive"
                        :title="imageUploadBlocked ? imageUploadBlockedTitle : imageAttachTitle"
                        :aria-label="imageUploadBlocked ? imageUploadBlockedTitle : 'Додати зображення до повідомлення'"
                        @click="$refs.imageInput && $refs.imageInput.click()"
                    >
                        <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
                            />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="rp-focusable rounded-md border border-[var(--rp-border-subtle)] p-2 text-[var(--rp-text)] hover:bg-[var(--rp-surface-elevated)] disabled:opacity-50"
                        :disabled="sending || uploadingImage || imageUploadBlocked || !imageUploadContextActive"
                        title="Мої зображення"
                        aria-label="Мої зображення"
                        @click="openMyImagesModal"
                    >
                        <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M22 16V4c0-1.1-.9-2-2-2H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2zm-11-4l2.03 2.71L16 11l4 5H8l3-4zM2 6v14c0 1.1.9 2 2 2h14v-2H4V6H2z"
                            />
                        </svg>
                    </button>
                </div>
                <input
                    ref="imageInput"
                    type="file"
                    class="hidden"
                    accept="image/jpeg,image/png,image/gif,image/webp"
                    @change="onChatImageSelected"
                />
            </div>
            <ChatRoomComposerAttachmentPreviews
                :edit-post-id="null"
                edit-existing-image-url=""
                :pending-image-id="pendingImageId"
                :pending-preview-url="pendingPreviewUrl"
                :sending="sending"
                :uploading-image="uploadingImage"
                @clear-pending-image="onClearPendingImageClick"
            />
            <ChatMyImagesModal
                :open="myImagesModalOpen"
                :ensure-sanctum="ensureSanctum"
                @close="myImagesModalOpen = false"
                @select="onLibraryImageSelected"
            />
            <RpButton
                native-type="submit"
                class="w-full"
                :disabled="sending || uploadingImage || !canSubmitPrivate"
            >
                Надіслати
            </RpButton>
        </form>
    </div>
</template>

<script>
import ChatMessageBody from './chat/feed/ChatMessageBody.vue';
import ChatMyImagesModal from './chat/composer/ChatMyImagesModal.vue';
import ChatRoomComposerAttachmentPreviews from './chat/composer/ChatRoomComposerAttachmentPreviews.vue';
import { chatComposerImageUploadMixin } from '../mixins/chatComposerImageUploadMixin';
import { formatChatMessageTimeLocal, isoUtcFromUnixSeconds } from '../utils/formatChatMessageTime';
import { messageHasBlockMedia } from '../utils/chatMessageBodyParse';
import { openImageLightbox } from '../utils/imageLightboxStore';

export default {
    name: 'PrivateChatPanel',
    mixins: [chatComposerImageUploadMixin],
    components: { ChatMessageBody, ChatMyImagesModal, ChatRoomComposerAttachmentPreviews },
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
        messageMaxLength: {
            type: Number,
            default: 4000,
        },
    },
    data() {
        return {
            composerTextareaRefName: 'privateComposer',
        };
    },
    computed: {
        privateComposerPlaceholder() {
            if (this.showSlashDocs) {
                return 'Повідомлення… (команда /clear — очистити тред)';
            }

            return 'Повідомлення — Enter надішле, Shift+Enter — новий рядок';
        },
        imageUploadContextActive() {
            return Boolean(this.peer);
        },
        canSubmitPrivate() {
            const t = String(this.composerText || '').trim();

            return Boolean(t || this.pendingImageId);
        },
    },
    watch: {
        messages: {
            handler() {
                const lastId = this.messages.length ? Number(this.messages[this.messages.length - 1].id) : null;
                const prevLastId = this._prevLastId;
                this._prevLastId = lastId;
                if (prevLastId === null) {
                    this.$nextTick(() => this.scrollBottom());

                    return;
                }
                if (lastId !== null && prevLastId !== null && lastId !== prevLastId) {
                    this.$nextTick(() => this.scrollBottom());
                }
            },
            deep: true,
        },
        peer() {
            this._prevLastId = null;
            this.clearPendingChatImage();
            this.$nextTick(() => this.scrollBottom());
        },
    },
    mounted() {
        this.$nextTick(() => this.setupTopObserver());
    },
    beforeDestroy() {
        this.teardownTopObserver();
    },
    methods: {
        privatePostHasBlockMedia(m) {
            return messageHasBlockMedia(m && m.body);
        },
        onPrivateAttachmentLightbox(event, m) {
            const url = m && m.image && m.image.url;
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
        setupTopObserver() {
            this.teardownTopObserver();
            const root = this.$refs.privateList;
            const target = this.$refs.topSentinel;
            if (!root || !target || typeof IntersectionObserver === 'undefined') {
                return;
            }
            this._topObs = new IntersectionObserver(
                (entries) => {
                    const hit = entries.some((e) => e.isIntersecting);
                    if (!hit) {
                        return;
                    }
                    this.$emit('top-visible');
                },
                { root, rootMargin: '48px 0px 0px 0px', threshold: 0 },
            );
            this._topObs.observe(target);
        },
        teardownTopObserver() {
            if (this._topObs) {
                this._topObs.disconnect();
                this._topObs = null;
            }
        },
        onSubmit() {
            if (!this.canSubmitPrivate || this.sending || this.uploadingImage) {
                return;
            }
            const t = String(this.composerText || '').trim();
            this.$emit('send', {
                message: t,
                imageId: this.pendingImageId,
                imagePreviewUrl: this.pendingPreviewUrl || '',
            });
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
        onClearPendingImageClick() {
            this.clearPendingChatImage();
        },
        /** Після успішної відправки — батько викликає через ref. */
        clearAfterSuccessfulSend() {
            this.clearPendingChatImage();
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
