<template>
    <div
        ref="root"
        data-rp-user-badge-inline-menu="true"
        class="rp-sidebar-user-inline-menu mt-2 w-full overflow-hidden rounded-xl shadow-sm outline-none"
        style="
            background: var(--rp-burger-accent-bg);
            color: var(--rp-burger-accent-fg);
            border: 1px solid var(--rp-burger-accent-divider);
        "
        role="menu"
        aria-label="Меню користувача"
        tabindex="-1"
        @keydown="onMenuKeydown"
    >
        <template v-for="(item, idx) in flatItems">
            <div
                v-if="item.type === 'sep'"
                :key="'sep-' + idx"
                class="h-px"
                style="background: var(--rp-burger-accent-divider)"
            />
            <button
                v-else
                :key="item.id"
                :ref="'mi-' + item.id"
                type="button"
                role="menuitem"
                class="rp-focusable flex w-full items-center gap-3 px-3 py-3 text-left text-sm font-medium transition-colors hover:brightness-95"
                style="color: var(--rp-burger-accent-fg)"
                :tabindex="focusedIndex === item.focusIndex ? 0 : -1"
                @click="onPick(item.id)"
            >
                <span
                    v-if="item.id === 'commands'"
                    class="inline-flex h-5 w-5 shrink-0 items-center justify-center opacity-90"
                    aria-hidden="true"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"
                        />
                    </svg>
                </span>
                <span
                    v-else-if="item.id === 'profile'"
                    class="inline-flex h-5 w-5 shrink-0 items-center justify-center opacity-90"
                    aria-hidden="true"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"
                        />
                    </svg>
                </span>
                <span
                    v-else-if="item.id === 'settings'"
                    class="inline-flex h-5 w-5 shrink-0 items-center justify-center opacity-90"
                    aria-hidden="true"
                >
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"
                        />
                    </svg>
                </span>
                <span v-else class="w-5 shrink-0" aria-hidden="true" />
                {{ item.label }}
            </button>
        </template>
    </div>
</template>

<script>
import { buildUserBadgeMenuItems } from '../lib/userBadgeMenuItems';

export default {
    name: 'UserBadgeInlineActionPanel',
    props: {
        mode: {
            type: String,
            default: 'other',
        },
        viewer: {
            type: Object,
            default: null,
        },
        target: {
            type: Object,
            default: null,
        },
    },
    data() {
        return {
            focusedIndex: 0,
        };
    },
    computed: {
        flatItems() {
            return buildUserBadgeMenuItems(this.mode, this.viewer, this.target);
        },
    },
    watch: {
        mode() {
            this.focusedIndex = 0;
        },
        target: {
            deep: true,
            handler() {
                this.focusedIndex = 0;
            },
        },
    },
    mounted() {
        this.focusedIndex = 0;
        this.$nextTick(() => {
            this.focusCurrent();
        });
    },
    methods: {
        focusCurrent() {
            const list = this.flatItems.filter((i) => i.type === 'btn');
            const row = list[this.focusedIndex];
            if (!row) {
                return;
            }
            const refKey = 'mi-' + row.id;
            const refs = this.$refs[refKey];
            const el = Array.isArray(refs) ? refs[0] : refs;
            if (el && typeof el.focus === 'function') {
                el.focus();
            }
        },
        onPick(id) {
            this.$emit('pick', id);
            this.$emit('close');
        },
        onMenuKeydown(e) {
            const list = this.flatItems.filter((i) => i.type === 'btn');
            if (list.length === 0) {
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.focusedIndex = (this.focusedIndex + 1) % list.length;
                this.focusCurrent();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.focusedIndex = (this.focusedIndex - 1 + list.length) % list.length;
                this.focusCurrent();
            } else if (e.key === 'Home') {
                e.preventDefault();
                this.focusedIndex = 0;
                this.focusCurrent();
            } else if (e.key === 'End') {
                e.preventDefault();
                this.focusedIndex = list.length - 1;
                this.focusCurrent();
            }
        },
    },
};
</script>
