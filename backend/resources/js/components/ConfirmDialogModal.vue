<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[75] flex items-end justify-center bg-black/40 p-4 sm:items-center"
            role="presentation"
            @click.self="onCancel"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                :aria-describedby="descId"
                class="w-full max-w-md rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] p-4 shadow-xl"
                tabindex="-1"
            >
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                    {{ title }}
                </h2>
                <p :id="descId" class="mt-2 text-sm text-[var(--rp-text-muted)]">
                    {{ body }}
                </p>
                <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                    <button
                        ref="cancelBtn"
                        type="button"
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
            </div>
        </div>
    </Teleport>
</template>

<script>
import {
    captureActiveElement,
    handleModalTabCycle,
    restoreFocusElement,
} from '../utils/modalFocusTrap';

let confirmSeq = 0;

export default {
    name: 'ConfirmDialogModal',
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
            titleId: `confirm-dialog-title-${confirmSeq}`,
            descId: `confirm-dialog-desc-${confirmSeq}`,
            focusBeforeModal: null,
        };
    },
    watch: {
        open(v) {
            if (v) {
                this.focusBeforeModal = captureActiveElement();
                document.addEventListener('keydown', this.onModalRootKeydown, true);
                this.$nextTick(() => {
                    const b = this.$refs.cancelBtn;
                    if (b && typeof b.focus === 'function') {
                        b.focus();
                    } else {
                        const p = this.$refs.panel;
                        if (p && typeof p.focus === 'function') {
                            p.focus();
                        }
                    }
                });
            } else {
                document.removeEventListener('keydown', this.onModalRootKeydown, true);
                restoreFocusElement(this.focusBeforeModal);
                this.focusBeforeModal = null;
            }
        },
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.onModalRootKeydown, true);
        restoreFocusElement(this.focusBeforeModal);
    },
    methods: {
        onCancel() {
            this.$emit('close');
        },
        onConfirm() {
            this.$emit('confirm');
        },
        onModalRootKeydown(e) {
            if (!this.open) {
                return;
            }
            const panel = this.$refs.panel;
            if (!panel) {
                return;
            }
            if (e.key === 'Escape') {
                e.preventDefault();
                this.onCancel();

                return;
            }
            handleModalTabCycle(e, panel);
        },
    },
};
</script>
