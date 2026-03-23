<template>
    <div class="w-full">
        <label v-if="label" class="rp-label" :for="inputId">{{ label }}</label>
        <div ref="anchor" class="relative flex w-full items-stretch gap-1">
            <input
                :id="inputId"
                ref="input"
                v-model="query"
                type="text"
                class="rp-input rp-focusable min-w-0 flex-1"
                role="combobox"
                :aria-expanded="open ? 'true' : 'false'"
                :aria-controls="listboxId"
                aria-autocomplete="list"
                :aria-activedescendant="activeDescendantId"
                autocomplete="off"
                :placeholder="placeholder"
                @focus="onFocus"
                @blur="onBlur"
                @keydown.down.prevent="onArrowDown"
                @keydown.up.prevent="onArrowUp"
                @keydown.enter.prevent="onEnter"
                @keydown.escape="onEscape"
                @keydown.tab="onTab"
            />
            <button
                v-if="allowClear && normalizedValue"
                type="button"
                class="rp-focusable shrink-0 rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-surface-elevated)] px-2 text-sm text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface)]"
                aria-label="Очистити країну"
                tabindex="-1"
                @mousedown.prevent="clearSelection"
            >
                ×
            </button>
        </div>
        <portal v-if="open">
            <ul
                :id="listboxId"
                ref="listEl"
                role="listbox"
                class="rp-country-combobox-list max-h-48 overflow-y-auto rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] py-1 shadow-lg"
                :style="listStyle"
                @mousedown.prevent
            >
                <template v-if="filteredRows.length">
                    <li
                        v-for="(row, i) in filteredRows"
                        :id="optionDomId(i)"
                        :key="row.code"
                        role="option"
                        :aria-selected="normalizedValue === row.code ? 'true' : 'false'"
                        class="cursor-pointer px-3 py-2 text-sm text-[var(--rp-text)] hover:bg-[var(--rp-surface-elevated)]"
                        :class="{
                            'bg-[var(--rp-surface-elevated)]': highlightIndex === i,
                        }"
                        @mousedown.prevent="selectCode(row.code)"
                    >
                        <span class="mr-2 font-mono text-xs text-[var(--rp-text-muted)]">{{ row.code }}</span>
                        <span>{{ row.labelUk }}</span>
                    </li>
                </template>
                <li v-else role="presentation" class="px-3 py-2 text-sm text-[var(--rp-text-muted)]">
                    Нічого не знайдено
                </li>
            </ul>
        </portal>
    </div>
</template>

<script>
import countryRows from '../../../data/iso3166-alpha2-uk.json';
import { filterCountryRows } from '../../utils/rpCountryComboboxFilter.js';

let seq = 0;

export default {
    name: 'RpCountryCombobox',
    props: {
        value: {
            type: String,
            default: '',
        },
        inputId: {
            type: String,
            required: true,
        },
        label: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: 'Пошук за назвою або кодом…',
        },
        allowClear: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        seq += 1;

        return {
            instanceId: seq,
            allRows: countryRows,
            open: false,
            query: '',
            highlightIndex: 0,
            blurTimer: null,
            listStyle: {},
            onWinScroll: null,
        };
    },
    computed: {
        listboxId() {
            return `rp-country-lb-${this.instanceId}`;
        },
        normalizedValue() {
            const v = this.value != null ? String(this.value).trim().toUpperCase() : '';

            return v.length === 2 ? v : '';
        },
        filteredRows() {
            return filterCountryRows(this.allRows, this.query);
        },
        activeDescendantId() {
            if (!this.open || !this.filteredRows.length) {
                return '';
            }
            const i = Math.max(0, Math.min(this.highlightIndex, this.filteredRows.length - 1));

            return this.optionDomId(i);
        },
    },
    watch: {
        value: {
            immediate: true,
            handler() {
                if (!this.open) {
                    this.syncQueryFromValue();
                }
            },
        },
    },
    beforeDestroy() {
        this.detachWindowListeners();
        if (this.blurTimer) {
            clearTimeout(this.blurTimer);
        }
    },
    methods: {
        labelForCode(code) {
            if (!code || code.length !== 2) {
                return '';
            }
            const row = this.allRows.find((r) => r.code === code);

            return row ? row.labelUk : '';
        },
        syncQueryFromValue() {
            const code = this.normalizedValue;
            this.query = code ? this.labelForCode(code) : '';
        },
        optionDomId(i) {
            return `rp-country-opt-${this.instanceId}-${i}`;
        },
        positionList() {
            const anchor = this.$refs.anchor;
            if (!anchor || typeof anchor.getBoundingClientRect !== 'function') {
                return;
            }
            const r = anchor.getBoundingClientRect();
            const gap = 4;
            const maxH = 192;
            const spaceBelow = window.innerHeight - r.bottom - gap;
            const spaceAbove = r.top - gap;
            let top = r.bottom + gap;
            let maxHeight = maxH;
            if (spaceBelow < 120 && spaceAbove > spaceBelow) {
                maxHeight = Math.min(maxH, Math.max(80, spaceAbove));
                top = r.top - gap - maxHeight;
            } else {
                maxHeight = Math.min(maxH, Math.max(80, spaceBelow));
            }
            this.listStyle = {
                position: 'fixed',
                left: `${r.left}px`,
                top: `${top}px`,
                width: `${r.width}px`,
                maxHeight: `${maxHeight}px`,
                zIndex: 200,
            };
        },
        attachWindowListeners() {
            if (this.onWinScroll) {
                return;
            }
            this.onWinScroll = () => {
                if (this.open) {
                    this.positionList();
                }
            };
            window.addEventListener('scroll', this.onWinScroll, true);
            window.addEventListener('resize', this.onWinScroll);
        },
        detachWindowListeners() {
            if (!this.onWinScroll) {
                return;
            }
            window.removeEventListener('scroll', this.onWinScroll, true);
            window.removeEventListener('resize', this.onWinScroll);
            this.onWinScroll = null;
        },
        onFocus() {
            if (this.blurTimer) {
                clearTimeout(this.blurTimer);
                this.blurTimer = null;
            }
            this.open = true;
            this.highlightIndex = 0;
            this.$nextTick(() => {
                this.positionList();
                this.attachWindowListeners();
            });
        },
        onBlur() {
            this.blurTimer = setTimeout(() => {
                this.open = false;
                this.detachWindowListeners();
                this.syncQueryFromValue();
            }, 120);
        },
        onArrowDown() {
            if (!this.open) {
                this.open = true;
                this.highlightIndex = 0;
                this.$nextTick(() => this.positionList());

                return;
            }
            const n = this.filteredRows.length;
            if (!n) {
                return;
            }
            this.highlightIndex = (this.highlightIndex + 1) % n;
            this.scrollActiveIntoView();
        },
        onArrowUp() {
            if (!this.open) {
                return;
            }
            const n = this.filteredRows.length;
            if (!n) {
                return;
            }
            this.highlightIndex = (this.highlightIndex - 1 + n) % n;
            this.scrollActiveIntoView();
        },
        scrollActiveIntoView() {
            this.$nextTick(() => {
                const list = this.$refs.listEl;
                if (!list || !list.children || !this.filteredRows.length) {
                    return;
                }
                const i = Math.max(0, Math.min(this.highlightIndex, this.filteredRows.length - 1));
                const active = list.children[i];
                if (active && typeof active.scrollIntoView === 'function') {
                    active.scrollIntoView({ block: 'nearest' });
                }
            });
        },
        onEnter() {
            if (!this.open) {
                return;
            }
            const row = this.filteredRows[this.highlightIndex];
            if (row) {
                this.selectCode(row.code);
            }
        },
        onEscape() {
            if (this.open) {
                this.open = false;
                this.detachWindowListeners();
                this.syncQueryFromValue();
            }
        },
        onTab() {
            this.open = false;
            this.detachWindowListeners();
        },
        selectCode(code) {
            this.$emit('input', code);
            this.open = false;
            this.detachWindowListeners();
            this.query = this.labelForCode(code);
            if (this.blurTimer) {
                clearTimeout(this.blurTimer);
                this.blurTimer = null;
            }
        },
        clearSelection() {
            this.$emit('input', '');
            this.query = '';
            this.open = false;
            this.detachWindowListeners();
        },
    },
};
</script>
