<template>
    <div>
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
            <RpButton
                variant="ghost"
                class="text-sm"
                :disabled="sending || uploadingImage"
                @click="$emit('clear-pending-image')"
            >
                Прибрати фото
            </RpButton>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ChatRoomComposerAttachmentPreviews',
    props: {
        editPostId: { default: null, validator: (v) => v === null || v === undefined || typeof v === 'number' },
        editExistingImageUrl: { type: String, default: '' },
        pendingImageId: { default: null, validator: (v) => v === null || v === undefined || typeof v === 'number' },
        pendingPreviewUrl: { type: String, default: '' },
        sending: { type: Boolean, default: false },
        uploadingImage: { type: Boolean, default: false },
    },
};
</script>
