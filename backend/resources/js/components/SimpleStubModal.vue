<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[75] flex items-end justify-center bg-black/40 p-4 sm:items-center"
            role="presentation"
            @click.self="close"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="w-full max-w-md rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] p-4 shadow-xl"
                tabindex="-1"
            >
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                    {{ title }}
                </h2>
                <p class="mt-2 text-sm text-[var(--rp-text-muted)]">
                    {{ body }}
                </p>
                <button type="button" class="rp-focusable rp-btn rp-btn-primary mt-4 w-full" @click="close">
                    Закрити
                </button>
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

let stubSeq = 0;

export default {
    name: 'SimpleStubModal',
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
    },
    data() {
        stubSeq += 1;

        return {
            titleId: `stub-modal-title-${stubSeq}`,
            focusBeforeModal: null,
        };
    },
    watch: {
        open(v) {
            if (v) {
                this.focusBeforeModal = captureActiveElement();
                document.addEventListener('keydown', this.onModalRootKeydown, true);
                this.$nextTick(() => {
                    const p = this.$refs.panel;
                    if (p && typeof p.focus === 'function') {
                        p.focus();
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
        close() {
            this.$emit('close');
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
                this.close();

                return;
            }
            handleModalTabCycle(e, panel);
        },
    },
};
</script>
