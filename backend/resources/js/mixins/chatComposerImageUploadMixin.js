/**
 * Спільна логіка завантаження зображень у композер (кімната + приват, T195).
 * Хост задає `composerTextareaRefName` та `imageUploadContextActive`.
 */
import {
    CHAT_IMAGE_MAX_BYTES,
    formatChatImageMaxLabel,
    getFirstClipboardImageFile,
    validateChatImageFileForUpload,
} from '../utils/chatComposerImageUpload';
import { showError, showProgress, showWarning } from '../utils/rpToastStack';

export const chatComposerImageUploadMixin = {
    props: {
        ensureSanctum: { type: Function, required: true },
        isGuest: { type: Boolean, default: false },
        /** Модератор вимкнув завантаження (`chat_upload_disabled` у профілі). */
        chatUploadDisabled: { type: Boolean, default: false },
        /** З `GET /api/v1/chat/settings` → `max_chat_image_upload_bytes` (T86). */
        maxChatImageUploadBytes: {
            default: null,
            validator: (v) => v === null || v === undefined || (typeof v === 'number' && Number.isFinite(v)),
        },
        /** false — не викликати upload (немає активної кімнати / peer). */
        imageUploadContextActive: { type: Boolean, default: true },
    },
    data() {
        return {
            /** Ім’я ref textarea у шаблоні хоста (напр. `chatComposer` / `privateComposer`). */
            composerTextareaRefName: 'chatComposer',
            pendingImageId: null,
            pendingPreviewUrl: '',
            uploadingImage: false,
            myImagesModalOpen: false,
        };
    },
    computed: {
        effectiveChatImageMaxBytes() {
            const n = Number(this.maxChatImageUploadBytes);

            return Number.isFinite(n) && n > 0 ? n : CHAT_IMAGE_MAX_BYTES;
        },
        imageAttachTitle() {
            const label = formatChatImageMaxLabel(this.effectiveChatImageMaxBytes);

            return `Додати зображення (JPEG, PNG, GIF, WebP, до ${label})`;
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
    },
    methods: {
        clearPendingChatImage() {
            this.pendingImageId = null;
            this.pendingPreviewUrl = '';
            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = '';
            }
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
            if (this.imageUploadBlocked || !this.imageUploadContextActive || this.uploadingImage) {
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
            this.focusComposerAfterPendingImageAttached();
        },
        focusComposerAfterPendingImageAttached() {
            this.$nextTick(() => {
                if (this.emojiModalOpen || this.myImagesModalOpen) {
                    return;
                }
                this.focusComposerTextareaEnd();
            });
        },
        focusComposerTextareaEnd() {
            const el = this.$refs[this.composerTextareaRefName];
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
            if (!this.imageUploadContextActive) {
                return;
            }
            if (this.sending || this.uploadingImage) {
                showWarning('Зачекайте завершення поточної дії перед вставкою зображення.');

                return;
            }
            this.uploadChatImageFile(file);
        },
        async onChatImageSelected(e) {
            const input = e.target;
            const file = input.files && input.files[0];
            if (input) {
                input.value = '';
            }
            if (!file || !this.imageUploadContextActive || this.imageUploadBlocked) {
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
                    onUploadProgress: (ev) => {
                        if (ev.lengthComputable && ev.total > 0) {
                            progress.setPercent(Math.round((100 * ev.loaded) / ev.total));
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
