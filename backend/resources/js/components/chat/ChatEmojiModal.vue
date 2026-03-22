<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[78] flex items-end justify-center bg-black/40 p-4 sm:items-center"
            role="presentation"
            @click.self="close"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                class="flex max-h-[min(85vh,32rem)] w-full max-w-md flex-col rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] shadow-xl"
                tabindex="-1"
            >
                <div class="flex shrink-0 items-start justify-between gap-2 border-b border-[var(--rp-border-subtle)] p-4">
                    <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                        Смайли
                    </h2>
                    <button type="button" class="rp-focusable rp-btn rp-btn-ghost text-sm" @click="close">
                        Закрити
                    </button>
                </div>
                <div class="shrink-0 border-b border-[var(--rp-border-subtle)] p-4">
                    <label class="rp-label" :for="searchId">Шукати смайли</label>
                    <input
                        :id="searchId"
                        ref="searchInput"
                        v-model.trim="searchQuery"
                        type="search"
                        autocomplete="off"
                        class="rp-input rp-focusable mt-1 w-full"
                        placeholder="Код або назва…"
                        maxlength="80"
                    />
                </div>
                <div class="min-h-0 flex-1 overflow-y-auto p-4">
                    <p v-if="filtered.length === 0" class="text-sm text-[var(--rp-text-muted)]" role="status">
                        Нічого не знайдено. Спробуйте інший запит.
                    </p>
                    <div
                        v-else
                        class="grid grid-cols-4 gap-2 sm:grid-cols-5"
                        role="list"
                    >
                        <button
                            v-for="it in filtered"
                            :key="it.code"
                            type="button"
                            class="rp-focusable flex flex-col items-center gap-1 rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-row-even)] px-1 py-2 text-center"
                            role="listitem"
                            :title="':' + it.code + ':'"
                            :aria-label="'Вставити смайл ' + ':' + it.code + ':'"
                            @click="pick(it)"
                        >
                            <span class="text-2xl leading-none" aria-hidden="true">{{ it.glyph }}</span>
                            <span class="max-w-full truncate font-mono text-[0.65rem] text-[var(--rp-text-muted)]">
                                :{{ it.code }}:
                            </span>
                        </button>
                    </div>
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
} from '../../utils/modalFocusTrap';
import { filterEmojiCatalog } from '../../utils/chatEmojiCatalog';

let modalSeq = 0;

export default {
    name: 'ChatEmojiModal',
    props: {
        open: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        modalSeq += 1;

        return {
            titleId: `chat-emoji-title-${modalSeq}`,
            searchId: `chat-emoji-search-${modalSeq}`,
            searchQuery: '',
            focusBeforeModal: null,
        };
    },
    computed: {
        filtered() {
            return filterEmojiCatalog(this.searchQuery);
        },
    },
    watch: {
        open(v) {
            if (v) {
                this.focusBeforeModal = captureActiveElement();
                this.searchQuery = '';
                document.addEventListener('keydown', this.onModalRootKeydown, true);
                this.$nextTick(() => {
                    const inp = this.$refs.searchInput;
                    if (inp && typeof inp.focus === 'function') {
                        inp.focus();
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
        pick(it) {
            if (!it || !it.code) {
                return;
            }
            this.$emit('select', { code: it.code });
            this.close();
        },
    },
};
</script>
