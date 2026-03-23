<template>
    <form
        class="flex shrink-0 flex-col border-t border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-composer-bg)]"
        @submit.prevent="emitSubmit"
    >
        <div ref="chatComposerChrome" class="shrink-0">
            <div class="rp-chat-toolbar rounded-none" role="toolbar" aria-label="Форматування та дії">
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    :class="{ 'rp-chat-toolbar-btn--active': composerStyle.bg }"
                    title="Колір тла повідомлення"
                    aria-label="Колір тла повідомлення"
                    :aria-expanded="formatPanel === 'bg' ? 'true' : 'false'"
                    aria-haspopup="true"
                    @click="toggleFormatPanel('bg')"
                >
                    <svg class="h-[18px] w-[18px]" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <circle cx="12" cy="12" r="8" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    :class="{ 'rp-chat-toolbar-btn--active': composerStyle.fg }"
                    title="Колір тексту"
                    aria-label="Колір тексту"
                    :aria-expanded="formatPanel === 'fg' ? 'true' : 'false'"
                    aria-haspopup="true"
                    :disabled="Boolean(composerStyle.bg)"
                    :aria-disabled="composerStyle.bg ? 'true' : 'false'"
                    @click="toggleFormatPanel('fg')"
                >
                    <svg class="h-[18px] w-[18px]" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="7.25" stroke="currentColor" stroke-width="2" />
                    </svg>
                </button>
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    :class="{ 'rp-chat-toolbar-btn--active': composerStyle.bold }"
                    title="Напівжирний"
                    aria-label="Напівжирний"
                    :aria-pressed="composerStyle.bold ? 'true' : 'false'"
                    @click="toggleComposerBold"
                >
                    <span class="text-sm font-bold" aria-hidden="true">B</span>
                </button>
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    :class="{ 'rp-chat-toolbar-btn--active': composerStyle.italic }"
                    title="Курсив"
                    aria-label="Курсив"
                    :aria-pressed="composerStyle.italic ? 'true' : 'false'"
                    @click="toggleComposerItalic"
                >
                    <span class="text-sm italic" aria-hidden="true">I</span>
                </button>
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    :class="{ 'rp-chat-toolbar-btn--active': composerStyle.underline }"
                    title="Підкреслення"
                    aria-label="Підкреслення"
                    :aria-pressed="composerStyle.underline ? 'true' : 'false'"
                    @click="toggleComposerUnderline"
                >
                    <span class="text-sm underline" aria-hidden="true">U</span>
                </button>
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    disabled
                    title="Група (згодом)"
                    aria-disabled="true"
                >
                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
                        />
                    </svg>
                </button>
                <span class="rp-chat-toolbar-spacer" aria-hidden="true" />
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    disabled
                    title="Довідник команд (згодом)"
                    aria-disabled="true"
                >
                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.17l-2 2V4h16v12zM11 5h2v2h-2V5zm0 3h2v6h-2V8z"
                        />
                    </svg>
                </button>
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    :disabled="isGuest || !selectedRoomId || uploadingImage || editPostId"
                    title="Мої зображення"
                    aria-label="Мої зображення"
                    @click="openMyImagesModal"
                >
                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
                        />
                    </svg>
                </button>
                <router-link
                    :to="archiveRoute"
                    class="rp-focusable rp-chat-toolbar-btn"
                    title="Архів чату"
                >
                    <span class="rp-sr-only">Архів чату</span>
                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"
                        />
                    </svg>
                </router-link>
                <button
                    type="button"
                    class="rp-focusable rp-chat-toolbar-btn"
                    title="Вийти з чату"
                    :disabled="loggingOut"
                    @click="$emit('logout')"
                >
                    <span class="rp-sr-only">Вийти</span>
                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"
                        />
                    </svg>
                </button>
            </div>
            <div
                v-if="formatPanel === 'bg'"
                class="rp-chat-fmt-palette flex flex-wrap items-center gap-2 border-t border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] px-2 py-2 sm:px-3"
                role="group"
                aria-label="Палітра тла повідомлення"
            >
                <button
                    type="button"
                    class="rp-focusable rounded border border-[var(--rp-chat-chrome-border)] px-2 py-1 text-[0.7rem] text-[var(--rp-text)]"
                    @click="clearComposerBg"
                >
                    Без тла
                </button>
                <button
                    v-for="opt in composerBgPalette"
                    :key="opt.key"
                    type="button"
                    class="rp-focusable rp-chat-fmt-swatch h-7 w-7 rounded border border-[var(--rp-chat-chrome-border)] shadow-sm"
                    :class="'rp-chat-fmt-swatch--' + opt.key"
                    :title="opt.label"
                    :aria-label="opt.label"
                    @click="setComposerBg(opt.key)"
                />
            </div>
            <div
                v-if="formatPanel === 'fg'"
                class="rp-chat-fmt-palette flex flex-wrap items-center gap-2 border-t border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] px-2 py-2 sm:px-3"
                role="group"
                aria-label="Палітра кольору тексту"
            >
                <button
                    type="button"
                    class="rp-focusable rounded border border-[var(--rp-chat-chrome-border)] px-2 py-1 text-[0.7rem] text-[var(--rp-text)]"
                    @click="clearComposerFg"
                >
                    Звичайний колір
                </button>
                <button
                    v-for="opt in composerFgPalette"
                    :key="opt.key"
                    type="button"
                    class="rp-focusable rp-chat-fmt-swatch h-7 w-7 rounded-full border-2 border-[var(--rp-chat-chrome-border)]"
                    :class="'rp-chat-fmt-swatch-fg--' + opt.key"
                    :title="opt.label"
                    :aria-label="opt.label"
                    @click="setComposerFg(opt.key)"
                />
            </div>
        </div>
        <div
            v-if="editPostId"
            class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] px-2 py-1.5 text-sm sm:px-3"
            role="status"
        >
            <span class="text-[var(--rp-text-muted)]">
                Редагування повідомлення
                <span v-if="editExistingImageUrl" class="block text-[0.7rem] font-normal text-[var(--rp-text-muted)]">
                    Зображення вкладення залишається; змінюються лише текст і форматування.
                </span>
            </span>
            <button
                type="button"
                class="rp-focusable rp-btn rp-btn-ghost text-sm"
                @click="cancelEdit"
            >
                Скасувати
            </button>
        </div>
        <label class="rp-sr-only" for="chat-composer">Повідомлення</label>
        <div class="rp-chat-composer-row">
            <button
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
                    placeholder="Повідомлення — Enter надішле, Shift+Enter — новий рядок; зображення можна вставити з буфера"
                    @keydown="onChatComposerKeydown"
                    @paste="onChatComposerPaste"
                    @input="syncComposerInputHeight"
                />
            </div>
            <div class="rp-chat-composer-trailing">
                <button
                    type="button"
                    class="rp-focusable rp-chat-composer-rail-btn"
                    :disabled="sending || uploadingImage || !selectedRoomId || isGuest || editPostId"
                    title="Додати зображення (JPEG, PNG, GIF, WebP, до 4 МБ)"
                    aria-label="Додати зображення до повідомлення"
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
        <p class="px-2 pb-1 text-[0.7rem] text-[var(--rp-text-muted)] sm:px-3">
            Приват: <code class="rounded bg-[var(--rp-chat-toolbar-bg)] px-1 font-mono text-[0.65rem]">/msg</code> нік текст.
        </p>
        <input
            ref="imageInput"
            type="file"
            class="hidden"
            accept="image/jpeg,image/png,image/gif,image/webp"
            @change="onChatImageSelected"
        />
        <div
            v-if="editPostId && editExistingImageUrl"
            class="mx-2 mb-2 flex flex-wrap items-center gap-3 rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-row-even)] p-2 sm:mx-3"
            role="region"
            aria-label="Поточне вкладене зображення"
        >
            <img
                :src="editExistingImageUrl"
                alt=""
                class="max-h-24 max-w-[12rem] rounded object-contain"
            />
            <p class="max-w-[14rem] text-[0.75rem] text-[var(--rp-text-muted)]">
                Це зображення лишиться після збереження. Замінити вкладення в цьому повідомленні не можна — надішли новий допис або видали повідомлення.
            </p>
        </div>
        <div
            v-if="pendingImageId && pendingPreviewUrl"
            class="mx-2 mb-2 flex flex-wrap items-center gap-3 rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-row-even)] p-2 sm:mx-3"
        >
            <img
                :src="pendingPreviewUrl"
                alt=""
                class="max-h-24 max-w-[12rem] rounded object-contain"
            />
            <button
                type="button"
                class="rp-focusable rp-btn rp-btn-ghost text-sm"
                :disabled="sending || uploadingImage"
                @click="clearPendingChatImage"
            >
                Прибрати фото
            </button>
        </div>
        <p
            v-if="imageUploadError"
            class="mx-2 mb-2 text-sm text-[var(--rp-error)] sm:mx-3"
            role="alert"
        >
            {{ imageUploadError }}
        </p>
        <ChatMyImagesModal
            :open="myImagesModalOpen"
            :ensure-sanctum="ensureSanctum"
            @close="myImagesModalOpen = false"
            @select="onLibraryImageSelected"
        />
        <ChatEmojiModal :open="emojiModalOpen" @close="emojiModalOpen = false" @select="onEmojiPicked" />
    </form>
</template>

<script>
import ChatEmojiModal from './ChatEmojiModal.vue';
import ChatMyImagesModal from './ChatMyImagesModal.vue';
import {
    getFirstClipboardImageFile,
    validateChatImageFileForUpload,
} from '../../utils/chatComposerImageUpload';
import {
    COMPOSER_BG_PALETTE,
    COMPOSER_FG_PALETTE,
    buildStylePayloadForApi,
    defaultComposerStyle,
    normalizePostStyleFromApi,
    readComposerStyleFromStorage,
    persistComposerStyle,
} from '../../utils/chatMessageStyle';

export default {
    name: 'ChatRoomComposer',
    components: { ChatEmojiModal, ChatMyImagesModal },
    props: {
        selectedRoomId: {
            default: null,
            validator: (v) => v === null || v === undefined || typeof v === 'number',
        },
        sending: { type: Boolean, default: false },
        loggingOut: { type: Boolean, default: false },
        isGuest: { type: Boolean, default: false },
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
            imageUploadError: '',
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
            this.imageUploadError = '';
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
            this.imageUploadError = '';
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
            const root = this.$refs.chatComposerChrome;
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
            if (this.isGuest) {
                this.imageUploadError = 'Завантаження зображень недоступне для гостя.';

                return;
            }
            if (!this.selectedRoomId) {
                return;
            }
            if (this.sending || this.uploadingImage) {
                this.imageUploadError = 'Зачекайте завершення поточної дії перед вставкою зображення.';

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
                const minPx = 44;
                const maxPx = 112;
                el.style.height = `${Math.min(Math.max(el.scrollHeight, minPx), maxPx)}px`;
            });
        },
        clearPendingChatImage() {
            this.pendingImageId = null;
            this.pendingPreviewUrl = '';
            this.imageUploadError = '';
            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = '';
            }
        },
        openMyImagesModal() {
            if (this.isGuest || !this.selectedRoomId || this.uploadingImage) {
                return;
            }
            this.myImagesModalOpen = true;
        },
        onLibraryImageSelected({ id, url }) {
            if (id == null || !url) {
                return;
            }
            this.imageUploadError = '';
            this.pendingImageId = id;
            this.pendingPreviewUrl = url;
        },
        async onChatImageSelected(e) {
            const input = e.target;
            const file = input.files && input.files[0];
            if (input) {
                input.value = '';
            }
            if (!file || !this.selectedRoomId || this.isGuest) {
                return;
            }
            await this.uploadChatImageFile(file);
        },
        async uploadChatImageFile(file) {
            const v = validateChatImageFileForUpload(file);
            if (!v.ok) {
                this.imageUploadError = v.message;
                this.clearPendingChatImage();

                return;
            }
            this.imageUploadError = '';
            this.uploadingImage = true;
            await this.ensureSanctum();
            try {
                const form = new FormData();
                form.append('image', file);
                const { data } = await window.axios.post('/api/v1/images', form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                this.pendingImageId = data.data.id;
                this.pendingPreviewUrl = data.data.url;
            } catch (err) {
                const msg = err.response?.data?.message || 'Не вдалося завантажити зображення.';
                this.imageUploadError = msg;
                this.clearPendingChatImage();
            } finally {
                this.uploadingImage = false;
            }
        },
    },
};
</script>
