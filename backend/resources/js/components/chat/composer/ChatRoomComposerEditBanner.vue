<template>
    <div
        v-if="editPostId"
        class="flex flex-wrap items-center justify-between gap-2 border-b border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] px-2 py-1.5 text-sm sm:px-3"
        role="status"
    >
        <span class="text-[var(--rp-text-muted)]">
            Редагування повідомлення
        </span>
        <RpButton variant="ghost" class="text-sm" @click="$emit('cancel-edit')">
            Скасувати
        </RpButton>
    </div>
</template>

<script>
export default {
    name: 'ChatRoomComposerEditBanner',
    props: {
        editPostId: { default: null, validator: (v) => v === null || v === undefined || typeof v === 'number' },
        editExistingImageUrl: { type: String, default: '' },
    },
};
</script>
