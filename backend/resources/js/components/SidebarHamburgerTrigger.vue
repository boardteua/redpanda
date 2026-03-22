<template>
    <button
        type="button"
        class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-transparent text-[var(--rp-chat-sidebar-fg)] transition-colors hover:bg-[var(--rp-chat-sidebar-tab-active-bg)] focus-visible:ring-2 focus-visible:ring-[var(--rp-focus-ring)]"
        data-rp-user-badge-menu-trigger="true"
        :aria-expanded="expanded ? 'true' : 'false'"
        :aria-haspopup="true"
        :aria-label="ariaLabel"
        @click.stop="$emit('activate', $event)"
    >
        <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
            <path d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
        </svg>
    </button>
</template>

<script>
export default {
    name: 'SidebarHamburgerTrigger',
    props: {
        expanded: {
            type: Boolean,
            default: false,
        },
        ariaLabel: {
            type: String,
            required: true,
        },
    },
};
</script>
