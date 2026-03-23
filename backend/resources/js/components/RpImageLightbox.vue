<template>
    <portal>
        <div
            v-if="lbOpen"
            class="fixed inset-0 flex items-center justify-center bg-black/80 p-3 sm:p-6"
            role="presentation"
            data-rp-image-lightbox-backdrop
            :style="{ zIndex: zIndexNum }"
            @click.self="close"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                aria-label="Зображення у повідомленні"
                tabindex="-1"
                class="flex max-h-[min(92vh,100%)] max-w-[min(96vw,100%)] flex-col overflow-hidden rounded-lg border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-surface)] shadow-xl outline-none"
                @click.stop
            >
                <div class="flex shrink-0 justify-end border-b border-[var(--rp-border-subtle)] p-2">
                    <button
                        type="button"
                        class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)] hover:text-[var(--rp-text)]"
                        aria-label="Закрити"
                        data-rp-initial-focus
                        @click="close"
                    >
                        <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                            />
                        </svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-auto p-2">
                    <img
                        :src="lbSrc"
                        :alt="lbAlt"
                        class="mx-auto max-h-[min(85vh,880px)] w-auto max-w-full object-contain"
                    />
                </div>
            </div>
        </div>
    </portal>
</template>

<script>
import { closeImageLightbox, imageLightboxStore } from '../utils/imageLightboxStore';
import {
    getModalFocusables,
    handleModalTabCycle,
} from '../utils/modalFocusTrap';

export default {
    name: 'RpImageLightbox',
    data() {
        return {
            bodyOverflowBefore: '',
        };
    },
    computed: {
        lbOpen() {
            return imageLightboxStore.open;
        },
        lbSrc() {
            return imageLightboxStore.src;
        },
        lbAlt() {
            return imageLightboxStore.alt;
        },
        zIndexNum() {
            return 200;
        },
    },
    watch: {
        lbOpen(v) {
            if (v) {
                this.bodyOverflowBefore = document.body.style.overflow;
                document.body.style.overflow = 'hidden';
                document.addEventListener('keydown', this.onModalRootKeydown, true);
                this.moveFocusInside();
            } else {
                document.body.style.overflow = this.bodyOverflowBefore;
                document.removeEventListener('keydown', this.onModalRootKeydown, true);
            }
        },
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.onModalRootKeydown, true);
        if (imageLightboxStore.open) {
            document.body.style.overflow = this.bodyOverflowBefore;
        }
    },
    methods: {
        close() {
            closeImageLightbox();
        },
        onModalRootKeydown(e) {
            if (!this.lbOpen) {
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
        moveFocusInside() {
            this.$nextTick(() => {
                const panel = this.$refs.panel;
                if (!panel) {
                    return;
                }
                const marked = panel.querySelector('[data-rp-initial-focus]');
                if (marked instanceof HTMLElement && typeof marked.focus === 'function') {
                    marked.focus();

                    return;
                }
                const list = getModalFocusables(panel);
                if (list.length > 0) {
                    list[0].focus();

                    return;
                }
                if (typeof panel.focus === 'function') {
                    panel.focus();
                }
            });
        },
    },
};
</script>
