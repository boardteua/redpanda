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
                aria-labelledby="commands-help-title"
                class="flex max-h-[min(85vh,32rem)] w-full max-w-lg flex-col rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] shadow-xl"
                tabindex="-1"
            >
                <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3">
                    <h2 id="commands-help-title" class="text-base font-semibold text-[var(--rp-text)]">
                        Довідник slash-команд
                    </h2>
                    <button
                        type="button"
                        class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)]"
                        aria-label="Закрити"
                        @click="close"
                    >
                        <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                            />
                        </svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-3">
                    <pre
                        class="whitespace-pre-wrap break-words font-sans text-xs leading-relaxed text-[var(--rp-text)]"
                    >{{ body }}</pre>
                </div>
                <p class="shrink-0 border-t border-[var(--rp-border-subtle)] px-4 py-2 text-xs text-[var(--rp-text-muted)]">
                    Текст дзеркалить docs/board-te-ua/commands.md (статичний). Парсер slash-команд у чаті може відрізнятися.
                </p>
            </div>
        </div>
    </Teleport>
</template>

<script>
import commandsMd from '../../markdown/board-te-ua-commands.md?raw';
import {
    captureActiveElement,
    handleModalTabCycle,
    restoreFocusElement,
} from '../utils/modalFocusTrap';

export default {
    name: 'CommandsHelpModal',
    props: {
        open: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        return {
            body: typeof commandsMd === 'string' ? commandsMd : String(commandsMd),
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
