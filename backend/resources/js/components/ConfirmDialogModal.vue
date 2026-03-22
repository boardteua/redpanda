<template>
    <RpModal
        :open="open"
        variant="card"
        :title="title"
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
                <button
                    type="button"
                    data-rp-initial-focus
                    class="rp-focusable rp-btn w-full border border-[var(--rp-border-subtle)] bg-transparent sm:w-auto"
                    @click="onCancel"
                >
                    {{ cancelLabel }}
                </button>
                <button
                    type="button"
                    class="rp-focusable rp-btn w-full bg-[var(--rp-error)] text-white hover:opacity-90 sm:w-auto"
                    @click="onConfirm"
                >
                    {{ confirmLabel }}
                </button>
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
