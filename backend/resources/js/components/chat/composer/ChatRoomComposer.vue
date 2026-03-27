<template>
    <form
        class="flex min-w-0 w-full max-w-full shrink-0 flex-col border-t border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-composer-bg)]"
        @submit.prevent="emitSubmit"
    >
        <ChatRoomComposerToolbar
            ref="chatComposerChrome"
            :format-panel="formatPanel"
            :composer-style="composerStyle"
            :composer-bg-palette="composerBgPalette"
            :composer-fg-palette="composerFgPalette"
            :image-upload-blocked="imageUploadBlocked"
            :selected-room-id="selectedRoomId"
            :uploading-image="uploadingImage"
            :edit-post-id="editPostId"
            :logging-out="loggingOut"
            :archive-route="archiveRoute"
            @toggle-format-panel="toggleFormatPanel"
            @toggle-bold="toggleComposerBold"
            @toggle-italic="toggleComposerItalic"
            @toggle-underline="toggleComposerUnderline"
            @open-my-images="openMyImagesModal"
            @logout="$emit('logout')"
            @clear-bg="clearComposerBg"
            @set-bg="setComposerBg"
            @clear-fg="clearComposerFg"
            @set-fg="setComposerFg"
        />
        <ChatRoomComposerEditBanner
            :edit-post-id="editPostId"
            :edit-existing-image-url="editExistingImageUrl"
            @cancel-edit="cancelEdit"
        />
        <label class="rp-sr-only" for="chat-composer">Повідомлення</label>
        <div class="rp-chat-composer-row min-w-0 w-full max-w-full">
            <button
                ref="emojiOpenBtn"
                type="button"
                class="rp-focusable rp-chat-composer-rail-btn rounded-full"
                :disabled="!selectedRoomId"
                title="Смайли"
                aria-label="Відкрити вибір смайлів"
                @click="emojiModalOpen = true"
            >
                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"
                    />
                </svg>
            </button>
            <div class="rp-chat-composer-slot min-w-0 flex-[1_1_12rem]">
                <textarea
                    id="chat-composer"
                    ref="chatComposer"
                    v-model="composerText"
                    class="rp-focusable rp-chat-composer-input w-full"
                    :class="{ 'font-mono': composerLeadingSlash }"
                    :maxlength="messageMaxLength"
                    rows="1"
                    :disabled="sending || uploadingImage || !selectedRoomId"
                    :placeholder="composerPlaceholder"
                    @keydown="onChatComposerKeydown"
                    @paste="onChatComposerPaste"
                    @input="syncComposerInputHeight"
                />
            </div>
            <div class="rp-chat-composer-trailing">
                <button
                    type="button"
                    class="rp-focusable rp-chat-composer-rail-btn"
                    :disabled="sending || uploadingImage || !selectedRoomId || imageUploadBlocked || editPostId"
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
                    type="submit"
                    class="rp-focusable rp-chat-send-primary"
                    :disabled="
                        sending
                        || uploadingImage
                        || !selectedRoomId
                        || !canSubmitComposer
                    "
                    :title="editPostId ? 'Зберегти зміни' : 'Надіслати повідомлення'"
                    :aria-label="editPostId ? 'Зберегти зміни повідомлення' : 'Надіслати повідомлення'"
                >
                    <svg class="h-5 w-5 shrink-0" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                    </svg>
                </button>
            </div>
        </div>
        <input
            ref="imageInput"
            type="file"
            class="hidden"
            accept="image/jpeg,image/png,image/gif,image/webp"
            @change="onChatImageSelected"
        />
        <ChatRoomComposerAttachmentPreviews
            :edit-post-id="editPostId"
            :edit-existing-image-url="editExistingImageUrl"
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
        <ChatEmojiModal
            :open="emojiModalOpen"
            :get-anchor="getEmojiAnchorEl"
            @close="emojiModalOpen = false"
            @select="onEmojiPicked"
        />
    </form>
</template>

<script>
import ChatEmojiModal from './ChatEmojiModal.vue';
import ChatMyImagesModal from './ChatMyImagesModal.vue';
import ChatRoomComposerAttachmentPreviews from './ChatRoomComposerAttachmentPreviews.vue';
import ChatRoomComposerEditBanner from './ChatRoomComposerEditBanner.vue';
import ChatRoomComposerToolbar from './ChatRoomComposerToolbar.vue';
import {
    CHAT_IMAGE_MAX_BYTES,
    formatChatImageMaxLabel,
    getFirstClipboardImageFile,
    validateChatImageFileForUpload,
} from '../../../utils/chatComposerImageUpload';
import { showError, showProgress, showWarning } from '../../../utils/rpToastStack';
import {
    COMPOSER_BG_PALETTE,
    COMPOSER_FG_PALETTE,
    buildStylePayloadForApi,
    defaultComposerStyle,
    normalizePostStyleFromApi,
    readComposerStyleFromStorage,
    persistComposerStyle,
} from '../../../utils/chatMessageStyle';

export default {
    name: 'ChatRoomComposer',
    components: {
        ChatEmojiModal,
        ChatMyImagesModal,
        ChatRoomComposerAttachmentPreviews,
        ChatRoomComposerEditBanner,
        ChatRoomComposerToolbar,
    },
    props: {
        selectedRoomId: {
            default: null,
            validator: (v) => v === null || v === undefined || typeof v === 'number',
        },
        sending: { type: Boolean, default: false },
        loggingOut: { type: Boolean, default: false },
        isGuest: { type: Boolean, default: false },
        /** Модератор вимкнув завантаження зображень (`chat_upload_disabled` у `auth/user`). */
        chatUploadDisabled: { type: Boolean, default: false },
        /** З `GET /api/v1/chat/settings` → `max_chat_image_upload_bytes` (T86). */
        maxChatImageUploadBytes: {
            default: null,
            validator: (v) => v === null || v === undefined || (typeof v === 'number' && Number.isFinite(v)),
        },
        /** Узгоджено з `StoreChatMessageRequest` / `UpdateChatMessageRequest` (T35). */
        messageMaxLength: {
            type: Number,
            default: 4000,
        },
        ensureSanctum: { type: Function, required: true },
    },
    data() {
        return {
            composerText: '',
            composerStyle: defaultComposerStyle(),
            formatPanel: null,
            pendingImageId: null,
            pendingPreviewUrl: '',
            uploadingImage: false,
            myImagesModalOpen: false,
            emojiModalOpen: false,
            editPostId: null,
            editHadFile: false,
            /** URL поточного вкладення під час редагування (лише прев’ю; PATCH не змінює file). */
            editExistingImageUrl: '',
        };
    },
    computed: {
        canSubmitComposer() {
            if (this.editPostId) {
                return Boolean(this.composerText.trim()) || this.editHadFile;
            }

            return Boolean(this.composerText.trim()) || Boolean(this.pendingImageId);
        },
        effectiveChatImageMaxBytes() {
            const n = Number(this.maxChatImageUploadBytes);

            return Number.isFinite(n) && n > 0 ? n : CHAT_IMAGE_MAX_BYTES;
        },
        imageAttachTitle() {
            const label = formatChatImageMaxLabel(this.effectiveChatImageMaxBytes);

            return `Додати зображення (JPEG, PNG, GIF, WebP, до ${label})`;
        },
        composerBgPalette() {
            return COMPOSER_BG_PALETTE;
        },
        composerFgPalette() {
            return COMPOSER_FG_PALETTE;
        },
        archiveRoute() {
            return {
                name: 'archive',
                query: this.selectedRoomId ? { room: String(this.selectedRoomId) } : {},
            };
        },
        /** T66: рядок починається з `/` після пробілів — підказка «команда» (моноширинність). */
        composerLeadingSlash() {
            const t = (this.composerText || '').replace(/^\s+/, '');

            return t.startsWith('/');
        },
        imageUploadBlocked() {
            return Boolean(this.isGuest || this.chatUploadDisabled);
        },
        imageUploadBlockedTitle() {
            if (this.isGuest) {
                return 'Завантаження зображень недоступне для гостя';
            }
            if (this.chatUploadDisabled) {
                return 'Модератор вимкнув завантаження зображень для вашого облікового запису';
            }

            return '';
        },
        composerPlaceholder() {
            return 'Shift+Enter — новий рядок;';
        },
    },
    watch: {
        composerStyle: {
            deep: true,
            handler(v) {
                persistComposerStyle(v);
            },
        },
        formatPanel(to) {
            document.removeEventListener('mousedown', this.onFormatPaletteDocMouseDown, true);
            if (to) {
                document.addEventListener('mousedown', this.onFormatPaletteDocMouseDown, true);
            }
        },
    },
    created() {
        this.composerStyle = readComposerStyleFromStorage();
    },
    mounted() {
        this.syncComposerInputHeight();
    },
    beforeDestroy() {
        document.removeEventListener('mousedown', this.onFormatPaletteDocMouseDown, true);
    },
    methods: {
        getSendPayload() {
            return {
                text: this.composerText.trim(),
                imageId: this.pendingImageId,
                stylePayload: buildStylePayloadForApi(this.composerStyle),
                editPostId: this.editPostId,
                editHadFile: this.editHadFile,
            };
        },
        getEditPostId() {
            return this.editPostId;
        },
        loadForEdit(message) {
            if (!message || message.post_id == null) {
                return;
            }
            this.editPostId = message.post_id;
            this.editHadFile = Boolean(message.image && message.image.id);
            this.editExistingImageUrl =
                message.image && message.image.url ? String(message.image.url) : '';
            this.composerText = message.post_message != null ? String(message.post_message) : '';
            this.clearPendingChatImage();
            const n = normalizePostStyleFromApi(message.post_style);
            if (n) {
                this.composerStyle = {
                    bold: n.bold,
                    italic: n.italic,
                    underline: n.underline,
                    bg: n.bg,
                    fg: n.fg,
                };
            } else {
                this.composerStyle = defaultComposerStyle();
            }
            this.formatPanel = null;
            this.$nextTick(() => {
                this.syncComposerInputHeight();
                const el = this.$refs.chatComposer;
                if (el && typeof el.focus === 'function') {
                    el.focus();
                    const len = this.composerText.length;
                    try {
                        el.setSelectionRange(len, len);
                    } catch {
                        /* */
                    }
                }
            });
        },
        clearEditIfPostId(postId) {
            if (this.editPostId == null || Number(this.editPostId) !== Number(postId)) {
                return;
            }
            this.cancelEdit();
        },
        cancelEdit() {
            this.editPostId = null;
            this.editHadFile = false;
            this.editExistingImageUrl = '';
            this.composerText = '';
            this.composerStyle = readComposerStyleFromStorage();
            this.clearPendingChatImage();
            this.formatPanel = null;
            this.$nextTick(() => this.syncComposerInputHeight());
        },
        resetAfterSend() {
            this.composerText = '';
            this.clearPendingChatImage();
            this.editPostId = null;
            this.editHadFile = false;
            this.editExistingImageUrl = '';
            this.formatPanel = null;
            this.$nextTick(() => this.syncComposerInputHeight());
        },
        /**
         * T124: після зняття `disabled` на textarea (батько виставляє `sending=false`) повернути фокус і курсор у кінець.
         */
        focusComposerAfterSend() {
            window.requestAnimationFrame(() => {
                this.$nextTick(() => {
                    this.$nextTick(() => this.focusComposerEnd());
                });
            });
        },
        appendToComposer(insertion) {
            if (insertion == null || insertion === '') {
                return;
            }
            const t = this.composerText;
            if (t.length === 0) {
                this.composerText = insertion;

                return;
            }
            const needsSpace = !/\s$/.test(t);
            this.composerText = needsSpace ? `${t} ${insertion}` : t + insertion;
        },
        /** T127: якір для модалу смайлів (нижній лівий кут біля кнопки). */
        getEmojiAnchorEl() {
            const el = this.$refs.emojiOpenBtn;

            return el instanceof HTMLElement ? el : null;
        },
        onEmojiPicked({ code }) {
            if (!code) {
                return;
            }
            this.appendToComposer(`:${code}:`);
            this.$nextTick(() => this.focusComposerEnd());
        },
        focusComposerEnd() {
            const el = this.$refs.chatComposer;
            if (!el || typeof el.focus !== 'function') {
                return;
            }
            el.focus();
            const len = this.composerText.length;
            try {
                el.setSelectionRange(len, len);
            } catch {
                /* */
            }
        },
        /**
         * T152: після успішного вкладення зображення (paste / file picker) — фокус у textarea, щоб Enter надсилав (T28).
         * Не забираємо фокус з відкритих модалей композера (T81).
         */
        focusComposerAfterPendingImageAttached() {
            if (this.emojiModalOpen || this.myImagesModalOpen) {
                return;
            }
            this.$nextTick(() => this.focusComposerEnd());
        },
        emitSubmit() {
            this.$emit('submit-message');
        },
        toggleFormatPanel(which) {
            if (which === 'fg' && this.composerStyle.bg) {
                return;
            }
            this.formatPanel = this.formatPanel === which ? null : which;
        },
        onFormatPaletteDocMouseDown(e) {
            const comp = this.$refs.chatComposerChrome;
            const root = comp && comp.$el;
            if (root && root.contains(e.target)) {
                return;
            }
            this.formatPanel = null;
        },
        toggleComposerBold() {
            this.composerStyle = { ...this.composerStyle, bold: !this.composerStyle.bold };
        },
        toggleComposerItalic() {
            this.composerStyle = { ...this.composerStyle, italic: !this.composerStyle.italic };
        },
        toggleComposerUnderline() {
            this.composerStyle = { ...this.composerStyle, underline: !this.composerStyle.underline };
        },
        setComposerBg(key) {
            this.composerStyle = { ...this.composerStyle, bg: key, fg: null };
            this.formatPanel = null;
        },
        clearComposerBg() {
            this.composerStyle = { ...this.composerStyle, bg: null };
            this.formatPanel = null;
        },
        setComposerFg(key) {
            this.composerStyle = { ...this.composerStyle, fg: key, bg: null };
            this.formatPanel = null;
        },
        clearComposerFg() {
            this.composerStyle = { ...this.composerStyle, fg: null };
            this.formatPanel = null;
        },
        textareaCaretLineIndex() {
            const el = this.$refs.chatComposer;
            if (!el || typeof el.value !== 'string') {
                return 0;
            }
            const pos = el.selectionStart ?? 0;

            return el.value.slice(0, pos).split('\n').length - 1;
        },
        textareaLineCount() {
            const el = this.$refs.chatComposer;
            if (!el || typeof el.value !== 'string') {
                return 1;
            }

            return el.value.split('\n').length;
        },
        onChatComposerKeydown(e) {
            if (e.isComposing || e.keyCode === 229) {
                return;
            }
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown') {
                if (!this.selectedRoomId || this.sending || this.uploadingImage) {
                    return;
                }
                const el = this.$refs.chatComposer;
                if (!el) {
                    return;
                }
                const line = this.textareaCaretLineIndex();
                const lines = this.textareaLineCount();
                if (e.key === 'ArrowUp' && line !== 0) {
                    return;
                }
                if (e.key === 'ArrowDown' && line !== lines - 1) {
                    return;
                }
                e.preventDefault();
                if (e.key === 'ArrowUp') {
                    if (this.editPostId == null) {
                        this.$emit('cycle-edit', { startLatest: true });
                    } else {
                        this.$emit('cycle-edit', { delta: -1 });
                    }
                } else if (this.editPostId != null) {
                    this.$emit('cycle-edit', { delta: 1 });
                }

                return;
            }
            if (e.key !== 'Enter') {
                return;
            }
            if (e.shiftKey) {
                this.$nextTick(() => this.syncComposerInputHeight());

                return;
            }
            e.preventDefault();
            this.emitSubmit();
        },
        onChatComposerPaste(e) {
            if (this.editPostId) {
                return;
            }
            const file = getFirstClipboardImageFile(e.clipboardData);
            if (!file) {
                return;
            }
            e.preventDefault();
            if (this.imageUploadBlocked) {
                showError(`${this.imageUploadBlockedTitle}.`);

                return;
            }
            if (!this.selectedRoomId) {
                return;
            }
            if (this.sending || this.uploadingImage) {
                showWarning('Зачекайте завершення поточної дії перед вставкою зображення.');

                return;
            }
            this.uploadChatImageFile(file);
        },
        syncComposerInputHeight() {
            this.$nextTick(() => {
                const el = this.$refs.chatComposer;
                if (!el || typeof el.style === 'undefined') {
                    return;
                }
                el.style.height = 'auto';
                const narrow =
                    typeof window !== 'undefined'
                    && window.matchMedia
                    && window.matchMedia('(max-width: 767px)').matches;
                /* Мобільний: одна лінія без «роздування»; десктоп: узгоджено з 2.75rem тулбар-кнопок */
                const minPx = narrow ? 36 : 44;
                const maxPx = 112;
                el.style.height = `${Math.min(Math.max(el.scrollHeight, minPx), maxPx)}px`;
            });
        },
        clearPendingChatImage() {
            this.pendingImageId = null;
            this.pendingPreviewUrl = '';
            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = '';
            }
        },
        onClearPendingImageClick() {
            this.clearPendingChatImage();
        },
        formatChatImageUploadError(err) {
            const d = err && err.response ? err.response.data : null;
            const bag = d && d.errors && typeof d.errors === 'object' ? d.errors : null;
            const flat = bag
                ? Object.values(bag)
                      .flat()
                      .filter((x) => typeof x === 'string' && x.trim())
                : [];
            if (flat.length) {
                return flat[0].trim();
            }
            if (d && typeof d.message === 'string' && d.message.trim()) {
                return d.message.trim();
            }
            if (!err.response) {
                return 'Мережа недоступна або сервер не відповів. Перевірте з’єднання і спробуйте знову.';
            }

            return 'Не вдалося завантажити зображення.';
        },
        openMyImagesModal() {
            if (this.imageUploadBlocked || !this.selectedRoomId || this.uploadingImage) {
                return;
            }
            this.myImagesModalOpen = true;
        },
        onLibraryImageSelected({ id, url }) {
            if (id == null || !url) {
                return;
            }
            this.pendingImageId = id;
            this.pendingPreviewUrl = url;
        },
        async onChatImageSelected(e) {
            const input = e.target;
            const file = input.files && input.files[0];
            if (input) {
                input.value = '';
            }
            if (!file || !this.selectedRoomId || this.imageUploadBlocked) {
                return;
            }
            await this.uploadChatImageFile(file);
        },
        async uploadChatImageFile(file) {
            if (this.imageUploadBlocked) {
                showError(`${this.imageUploadBlockedTitle}.`);
                this.clearPendingChatImage();

                return;
            }
            const v = validateChatImageFileForUpload(file, this.effectiveChatImageMaxBytes);
            if (!v.ok) {
                if (v.message) {
                    showError(v.message);
                }
                this.clearPendingChatImage();

                return;
            }
            const progress = showProgress('Завантаження зображення…');
            this.uploadingImage = true;
            try {
                await this.ensureSanctum();
                const form = new FormData();
                form.append('image', file);
                const { data } = await window.axios.post('/api/v1/images', form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                    onUploadProgress: (e) => {
                        if (e.lengthComputable && e.total > 0) {
                            progress.setPercent(Math.round((100 * e.loaded) / e.total));
                        }
                    },
                });
                progress.done();
                const row = data && data.data;
                if (!row || row.id == null || !row.url) {
                    showError('Сервер повернув неочікувану відповідь.');
                    this.clearPendingChatImage();

                    return;
                }
                this.pendingImageId = row.id;
                this.pendingPreviewUrl = row.url;
                this.focusComposerAfterPendingImageAttached();
            } catch (err) {
                progress.done();
                showError(this.formatChatImageUploadError(err));
                this.clearPendingChatImage();
            } finally {
                this.uploadingImage = false;
            }
        },
    },
};
</script>
