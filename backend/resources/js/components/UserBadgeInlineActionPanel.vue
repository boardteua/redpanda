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
                <UserBadgeMenuItemIcon :item-id="item.id" />
                {{ item.label }}
            </button>
        </template>
    </div>
</template>

<script>
import { buildUserBadgeMenuItems } from '../lib/userBadgeMenuItems';
import UserBadgeMenuItemIcon from './UserBadgeMenuItemIcon.vue';

export default {
    name: 'UserBadgeInlineActionPanel',
    components: { UserBadgeMenuItemIcon },
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
