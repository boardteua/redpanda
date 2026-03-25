<template>
    <portal>
        <div
            v-if="open"
            class="fixed inset-0"
            role="presentation"
            :style="{ zIndex: zIndexNum }"
        >
            <div
                class="absolute inset-0 bg-black/40"
                data-rp-modal-backdrop
                @click.self="close"
            />
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="titleId"
                :aria-describedby="usageHintId"
                tabindex="-1"
                class="absolute flex min-h-0 min-w-0 flex-col overflow-hidden rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] shadow-xl outline-none"
                :style="panelPositionStyle"
                @click.stop
            >
                <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] p-2">
                    <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                        Смайли
                    </h2>
                    <RpCloseButton @click="close" />
                </div>
                <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
                    <div class="shrink-0 border-b border-[var(--rp-border-subtle)] p-2">
                        <input
                            :id="searchId"
                            ref="searchInput"
                            v-model.trim="searchQuery"
                            data-rp-initial-focus
                            type="search"
                            autocomplete="off"
                            class="rp-input rp-focusable mt-1 w-full"
                            placeholder="Код або назва…"
                            maxlength="80"
                            :disabled="catalogLoading"
                            :aria-describedby="usageHintId"
                        />
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto p-2">
                        <p v-if="catalogLoading" class="text-sm text-[var(--rp-text-muted)]" role="status">
                            Завантаження каталогу…
                        </p>
                        <p v-else-if="catalogLoadError" role="alert" class="text-sm text-[var(--rp-error)]">
                            {{ catalogLoadError }}
                        </p>
                        <p v-else-if="catalogItems.length === 0" class="text-sm text-[var(--rp-text-muted)]" role="status">
                            Каталог порожній. Адміністратор може додати смайли в «Налаштування чату».
                        </p>
                        <p v-else-if="filtered.length === 0" class="text-sm text-[var(--rp-text-muted)]" role="status">
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
                                :title="it.display_name || ':' + it.code + ':'"
                                :aria-label="'Вставити смайл ' + ':' + it.code + ':'"
                                @click="pick(it)"
                            >
                                <img
                                    v-if="it.file"
                                    :src="'/emoticon/' + it.file"
                                    :alt="it.display_name || it.code"
                                    class="h-8 w-8 object-contain"
                                    loading="lazy"
                                    decoding="async"
                                />
                                <span
                                    v-else
                                    class="flex h-8 w-8 items-center justify-center text-2xl leading-none"
                                    aria-hidden="true"
                                >?</span>
                                <span class="max-w-full truncate font-mono text-[0.65rem] text-[var(--rp-text-muted)]">
                                    :{{ it.code }}:
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
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
} from '../../../utils/modalFocusTrap';
import { filterEmojiItems } from '../../../utils/chatEmojiCatalog';
import { loadChatEmoticonsCatalog } from '../../../utils/chatEmoticons';
import { getEmoticonUsageCounts, recordEmoticonUsage } from '../../../utils/chatEmoticonUsage';

let modalSeq = 0;

/** T127: якір біля кнопки смайлів; max-height + скрол усередині (LTR). */
const REM_PX = 16;
const MAX_PANEL_REM = 32;
const VIEWPORT_H_FRAC = 0.5;

export default {
    name: 'ChatEmojiModal',
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        /** Повертає HTMLElement кнопки-тригера (нижній лівий кут композера). */
        getAnchor: {
            type: Function,
            default: null,
        },
        zIndex: {
            type: [Number, String],
            default: 78,
        },
    },
    data() {
        modalSeq += 1;

        return {
            titleId: `chat-emoji-title-${modalSeq}`,
            searchId: `chat-emoji-search-${modalSeq}`,
            usageHintId: `chat-emoji-usage-hint-${modalSeq}`,
            searchQuery: '',
            catalogItems: [],
            catalogLoading: false,
            catalogLoadError: '',
            layout: {
                placement: 'above',
                left: 8,
                width: 320,
                bottom: 16,
                top: null,
                maxHeight: 320,
            },
            focusBeforeModal: null,
            boundReposition: null,
        };
    },
    computed: {
        zIndexNum() {
            const n = Number(this.zIndex);

            return Number.isFinite(n) ? n : 78;
        },
        panelPositionStyle() {
            const { placement, left, width, bottom, top, maxHeight } = this.layout;
            const base = {
                left: `${left}px`,
                width: `${width}px`,
                maxHeight: `${maxHeight}px`,
            };
            if (placement === 'above') {
                return { ...base, bottom: `${bottom}px`, top: 'auto' };
            }

            return { ...base, top: `${top}px`, bottom: 'auto' };
        },
        filtered() {
            const items = filterEmojiItems(this.catalogItems, this.searchQuery);
            const usage = getEmoticonUsageCounts();

            return [...items].sort((a, b) => {
                const ca = String(a.code || '').toLowerCase();
                const cb = String(b.code || '').toLowerCase();
                const ua = usage[ca] || 0;
                const ub = usage[cb] || 0;
                if (ub !== ua) {
                    return ub - ua;
                }

                return ca.localeCompare(cb, 'uk', { sensitivity: 'base' });
            });
        },
    },
    watch: {
        open(v) {
            if (v) {
                this.focusBeforeModal = captureActiveElement();
                document.addEventListener('keydown', this.onModalRootKeydown, true);
                this.boundReposition = () => this.scheduleReposition();
                window.addEventListener('resize', this.boundReposition, { passive: true });
                window.addEventListener('scroll', this.boundReposition, true);
                this.searchQuery = '';
                this.refreshCatalog();
                this.$nextTick(() => {
                    this.updatePosition();
                    this.moveFocusInside();
                });
            } else {
                document.removeEventListener('keydown', this.onModalRootKeydown, true);
                if (this.boundReposition) {
                    window.removeEventListener('resize', this.boundReposition);
                    window.removeEventListener('scroll', this.boundReposition, true);
                    this.boundReposition = null;
                }
                restoreFocusElement(this.focusBeforeModal);
                this.focusBeforeModal = null;
            }
        },
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.onModalRootKeydown, true);
        if (this.boundReposition) {
            window.removeEventListener('resize', this.boundReposition);
            window.removeEventListener('scroll', this.boundReposition, true);
        }
        restoreFocusElement(this.focusBeforeModal);
    },
    methods: {
        scheduleReposition() {
            if (!this.open) {
                return;
            }
            window.requestAnimationFrame(() => {
                if (this.open) {
                    this.updatePosition();
                }
            });
        },
        close() {
            this.$emit('close');
        },
        updatePosition() {
            const vw = window.innerWidth;
            const vh = window.innerHeight;
            const margin = 8;
            const gap = 8;
            const maxHCap = Math.min(vh * VIEWPORT_H_FRAC, MAX_PANEL_REM * REM_PX);
            const panelW = Math.min(28 * REM_PX, vw - margin * 2);

            const anchor = typeof this.getAnchor === 'function' ? this.getAnchor() : null;
            if (!(anchor instanceof HTMLElement)) {
                this.layout = {
                    placement: 'above',
                    left: margin,
                    width: panelW,
                    bottom: margin,
                    top: null,
                    maxHeight: Math.max(160, Math.min(maxHCap, vh - margin * 2)),
                };

                return;
            }

            const rect = anchor.getBoundingClientRect();
            const left = Math.max(margin, Math.min(rect.left, vw - panelW - margin));
            const spaceAbove = rect.top - margin;
            const spaceBelow = vh - rect.bottom - margin;
            const preferAbove = spaceAbove >= spaceBelow || spaceAbove >= maxHCap * 0.35;

            if (preferAbove && spaceAbove > 96) {
                const maxH = Math.max(160, Math.min(maxHCap, spaceAbove - gap));
                this.layout = {
                    placement: 'above',
                    left,
                    width: panelW,
                    bottom: vh - rect.top + gap,
                    top: null,
                    maxHeight: maxH,
                };
            } else {
                const maxH = Math.max(160, Math.min(maxHCap, spaceBelow - gap));
                this.layout = {
                    placement: 'below',
                    left,
                    width: panelW,
                    top: rect.bottom + gap,
                    bottom: null,
                    maxHeight: maxH,
                };
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
        async refreshCatalog() {
            this.catalogLoading = true;
            this.catalogLoadError = '';
            try {
                this.catalogItems = await loadChatEmoticonsCatalog();
            } catch {
                this.catalogLoadError = 'Не вдалося завантажити каталог смайлів.';
                this.catalogItems = [];
            } finally {
                this.catalogLoading = false;
                this.$nextTick(() => {
                    this.updatePosition();
                    if (this.open) {
                        this.moveFocusInside();
                    }
                });
            }
        },
        pick(it) {
            if (!it || !it.code) {
                return;
            }
            recordEmoticonUsage(it.code);
            this.$emit('select', { code: it.code });
            this.close();
        },
    },
};
</script>
