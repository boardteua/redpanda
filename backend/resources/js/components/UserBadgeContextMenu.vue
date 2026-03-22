<template>
    <Teleport to="body">
        <div
            ref="root"
            class="fixed inset-0 z-[70]"
            style="pointer-events: none"
            aria-hidden="true"
        >
            <div
                ref="menu"
                role="menu"
                aria-label="Меню користувача"
                class="rp-focusable fixed min-w-[14rem] max-w-[min(100vw-1rem,20rem)] rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] py-1 shadow-lg outline-none"
                :style="{ ...menuStyle, pointerEvents: 'auto' }"
                tabindex="-1"
                @keydown="onMenuKeydown"
            >
                <template v-for="(item, idx) in flatItems">
                    <hr
                        v-if="item.type === 'sep'"
                        :key="'sep-' + idx"
                        class="my-1 border-0 border-t border-[var(--rp-border-subtle)]"
                    />
                    <button
                        v-else
                        :key="item.id"
                        :ref="'mi-' + item.id"
                        type="button"
                        role="menuitem"
                        class="rp-focusable flex w-full px-3 py-2.5 text-left text-sm text-[var(--rp-text)] hover:bg-[var(--rp-surface-elevated)]"
                        :tabindex="focusedIndex === item.focusIndex ? 0 : -1"
                        @click="onPick(item.id)"
                    >
                        {{ item.label }}
                    </button>
                </template>
            </div>
        </div>
    </Teleport>
</template>

<script>
function isStaffRole(role) {
    return role === 'moderator' || role === 'admin';
}

export default {
    name: 'UserBadgeContextMenu',
    props: {
        anchorRect: {
            type: Object,
            default: null,
        },
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
        returnFocusEl: {
            type: Object,
            default: null,
        },
    },
    data() {
        return {
            menuStyle: {},
            focusedIndex: 0,
        };
    },
    computed: {
        flatItems() {
            const v = this.viewer;
            const t = this.target;
            const items = [];
            let fi = 0;
            const add = (id, label) => {
                items.push({ type: 'btn', id, label, focusIndex: fi });
                fi += 1;
            };
            const sep = () => {
                items.push({ type: 'sep' });
            };

            if (this.mode === 'self') {
                add('info', 'Інформація');
                add('commands', 'Команди');
                if (v && v.chat_role === 'admin') {
                    add('settings', 'Налаштування чату');
                }
                if (v && !v.guest) {
                    add('profile', 'Профіль');
                }
            } else if (t) {
                add('info', 'Інформація');
                add('private', 'Приватний чат');
                add('ignore', 'Ігнор');
                if (v && !t.guest) {
                    add('friend', 'Додати до друзів');
                }
                if (v && isStaffRole(v.chat_role) && t.id != null && v.id != null && Number(t.id) !== Number(v.id)) {
                    sep();
                    add('mute', 'Кляп…');
                    add('kick', 'Вигнати…');
                }
            }

            return items.filter((row) => row.type === 'sep' || row.type === 'btn');
        },
        focusableIds() {
            return this.flatItems.filter((i) => i.type === 'btn').map((i) => i.focusIndex);
        },
    },
    watch: {
        anchorRect: {
            deep: true,
            handler() {
                this.layoutMenu();
            },
        },
    },
    mounted() {
        this.layoutMenu();
        document.addEventListener('mousedown', this.onDocMouseDown, true);
        document.addEventListener('keydown', this.onDocKeydown, true);
        this.focusedIndex = 0;
        this.$nextTick(() => {
            this.focusCurrent();
        });
    },
    beforeDestroy() {
        document.removeEventListener('mousedown', this.onDocMouseDown, true);
        document.removeEventListener('keydown', this.onDocKeydown, true);
        this.restoreFocus();
    },
    methods: {
        layoutMenu() {
            const r = this.anchorRect;
            if (!r || typeof r.left !== 'number') {
                this.menuStyle = { left: '8px', top: '8px' };

                return;
            }
            const margin = 8;
            const gap = 4;
            const mw = 224;
            const mh = 320;
            let left = r.left;
            let top = r.bottom + gap;
            if (left + mw > window.innerWidth - margin) {
                left = Math.max(margin, window.innerWidth - margin - mw);
            }
            if (top + mh > window.innerHeight - margin) {
                top = Math.max(margin, r.top - gap - mh);
            }
            this.menuStyle = {
                left: `${Math.round(left)}px`,
                top: `${Math.round(top)}px`,
            };
        },
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
        restoreFocus() {
            const el = this.returnFocusEl;
            if (el && typeof el.focus === 'function') {
                try {
                    el.focus();
                } catch {
                    /* */
                }
            }
        },
        onDocMouseDown(e) {
            const menu = this.$refs.menu;
            if (menu && menu.contains(e.target)) {
                return;
            }
            if (e.target.closest && e.target.closest('[data-rp-user-badge-menu-trigger]')) {
                return;
            }
            this.$emit('close');
        },
        onDocKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                this.$emit('close');
            }
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
