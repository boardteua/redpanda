<template>
    <portal>
        <div
            v-if="open"
            class="fixed inset-0 flex items-end justify-center bg-black/40 p-4 sm:items-center"
            role="presentation"
            data-rp-modal-backdrop
            :style="{ zIndex: zIndexNum }"
            @click.self="onBackdropClick"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="labelledByAttr"
                :aria-describedby="describedByAttr"
                :aria-busy="busyAttr"
                tabindex="-1"
                :class="panelClasses"
            >
                <template v-if="variant === 'framed'">
                    <slot v-if="$slots.header" name="header" />
                    <div
                        v-else-if="title"
                        class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3"
                    >
                        <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                            {{ title }}
                        </h2>
                        <button
                            v-if="closable"
                            type="button"
                            class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)]"
                            aria-label="Закрити"
                            @click="emitClose"
                        >
                            <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                                />
                            </svg>
                        </button>
                    </div>
                    <div
                        v-if="scrollBody"
                        class="min-h-0 flex-1 overflow-y-auto"
                    >
                        <slot />
                    </div>
                    <div
                        v-else
                        class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden"
                    >
                        <slot />
                    </div>
                    <slot name="footer" />
                </template>

                <template v-else-if="variant === 'card'">
                    <div class="flex max-h-full min-h-0 flex-1 flex-col overflow-hidden">
                        <div class="min-h-0 flex-1 overflow-y-auto p-4">
                            <h2
                                v-if="title"
                                :id="titleId"
                                class="text-base font-semibold text-[var(--rp-text)]"
                            >
                                {{ title }}
                            </h2>
                            <slot />
                        </div>
                        <slot name="footer" />
                    </div>
                </template>
            </div>
        </div>
    </portal>
</template>

<script>
import {
    captureActiveElement,
    getModalFocusables,
    handleModalTabCycle,
    restoreFocusElement,
} from '../utils/modalFocusTrap';

let rpModalSeq = 0;

export default {
    name: 'RpModal',
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        /** Число для inline z-index (підкладка + панель). */
        zIndex: {
            type: [Number, String],
            default: 75,
        },
        /** max-w-* Tailwind: sm | md | lg | xl */
        size: {
            type: String,
            default: 'md',
        },
        maxHeightClass: {
            type: String,
            default: 'max-h-[min(85vh,90vh)]',
        },
        /**
         * framed — шапка (slot header або title), тіло, footer;
         * card — компактний блок з padding (напр. підтвердження, stub).
         */
        variant: {
            type: String,
            default: 'framed',
            validator: (v) => ['framed', 'card'].includes(v),
        },
        title: {
            type: String,
            default: '',
        },
        closable: {
            type: Boolean,
            default: true,
        },
        closeOnBackdrop: {
            type: Boolean,
            default: true,
        },
        /** У framed: обгорнути default slot у overflow-y-auto. */
        scrollBody: {
            type: Boolean,
            default: true,
        },
        /** Якщо є slot header без title — передайте id заголовка всередині шапки. */
        ariaLabelledby: {
            type: String,
            default: '',
        },
        ariaDescribedby: {
            type: String,
            default: '',
        },
        ariaBusy: {
            type: [Boolean, String],
            default: false,
        },
        panelClass: {
            type: String,
            default: '',
        },
        /** panel | first — якщо є [data-rp-initial-focus], він має пріоритет. */
        initialFocus: {
            type: String,
            default: 'panel',
            validator: (v) => ['panel', 'first'].includes(v),
        },
    },
    data() {
        rpModalSeq += 1;

        return {
            titleId: `rp-modal-title-${rpModalSeq}`,
            focusBeforeModal: null,
        };
    },
    computed: {
        zIndexNum() {
            const n = Number(this.zIndex);

            return Number.isFinite(n) ? n : 75;
        },
        sizeClass() {
            const map = {
                sm: 'max-w-sm',
                md: 'max-w-md',
                lg: 'max-w-lg',
                xl: 'max-w-xl',
            };

            return map[this.size] || map.md;
        },
        panelClasses() {
            return [
                'flex w-full flex-col rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] shadow-xl outline-none',
                this.sizeClass,
                this.maxHeightClass,
                this.panelClass,
            ];
        },
        labelledByAttr() {
            if (this.ariaLabelledby) {
                return this.ariaLabelledby;
            }
            if (this.title) {
                return this.titleId;
            }

            return undefined;
        },
        describedByAttr() {
            return this.ariaDescribedby || undefined;
        },
        busyAttr() {
            if (this.ariaBusy === true || this.ariaBusy === 'true') {
                return 'true';
            }

            return undefined;
        },
    },
    watch: {
        open(v) {
            if (v) {
                this.focusBeforeModal = captureActiveElement();
                document.addEventListener('keydown', this.onModalRootKeydown, true);
                this.moveFocusInside();
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
        emitClose() {
            this.$emit('close');
        },
        onBackdropClick() {
            if (this.closeOnBackdrop) {
                this.emitClose();
            }
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
                this.emitClose();

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
                if (this.initialFocus === 'first') {
                    const list = getModalFocusables(panel);
                    if (list.length > 0) {
                        list[0].focus();

                        return;
                    }
                }
                if (typeof panel.focus === 'function') {
                    panel.focus();
                }
            });
        },
    },
};
</script>
