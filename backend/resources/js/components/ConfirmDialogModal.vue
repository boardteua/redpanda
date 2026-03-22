<template>
    <RpModal
        :open="open"
        variant="card"
        :title="title"
        :z-index="zIndex"
        :aria-describedby="descId"
        @close="onCancel"
    >
        <p :id="descId" class="mt-2 text-sm text-[var(--rp-text-muted)]">
            {{ body }}
        </p>
        <template #footer>
            <div
                class="flex flex-col-reverse gap-2 border-t border-[var(--rp-border-subtle)] px-4 py-4 sm:flex-row sm:justify-end"
            >
                <RpButton
                    variant="outline"
                    class="w-full sm:w-auto"
                    data-rp-initial-focus
                    @click="onCancel"
                >
                    {{ cancelLabel }}
                </RpButton>
                <RpButton variant="danger" class="w-full sm:w-auto" @click="onConfirm">
                    {{ confirmLabel }}
                </RpButton>
            </div>
        </template>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';

let confirmSeq = 0;

export default {
    name: 'ConfirmDialogModal',
    components: { RpModal },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        title: {
            type: String,
            required: true,
        },
        body: {
            type: String,
            required: true,
        },
        confirmLabel: {
            type: String,
            default: 'Підтвердити',
        },
        cancelLabel: {
            type: String,
            default: 'Скасувати',
        },
        zIndex: {
            type: [Number, String],
            default: 75,
        },
    },
    data() {
        confirmSeq += 1;

        return {
            descId: `confirm-dialog-desc-${confirmSeq}`,
        };
    },
    methods: {
        onCancel() {
            this.$emit('close');
        },
        onConfirm() {
            this.$emit('confirm');
        },
    },
};
</script>
