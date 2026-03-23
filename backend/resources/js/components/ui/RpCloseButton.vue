<template>
    <button
        type="button"
        :class="buttonClass"
        :aria-label="ariaLabel"
        v-bind="$attrs"
        v-on="$listeners"
    >
        <svg :class="iconClass" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
            <path
                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
            />
        </svg>
    </button>
</template>

<script>
const VARIANTS = {
    modal:
        'rp-focusable flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)]',
    lightbox:
        'rp-focusable flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)] hover:text-[var(--rp-text)]',
    'sidebar-mobile':
        'rp-focusable flex h-12 w-12 shrink-0 cursor-pointer items-center justify-center rounded-lg text-white hover:bg-white/10',
    'sidebar-desktop':
        'rp-focusable flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-md text-[var(--rp-chat-sidebar-icon)] hover:bg-[var(--rp-chat-sidebar-tab-active-bg)] hover:text-[var(--rp-chat-sidebar-fg)]',
    toast: 'rp-toast__close rp-focusable cursor-pointer',
};

const ICON_SIZES = {
    modal: 'h-6 w-6',
    lightbox: 'h-6 w-6',
    'sidebar-mobile': 'h-9 w-9',
    'sidebar-desktop': 'h-6 w-6',
    toast: 'h-4 w-4',
};

export default {
    name: 'RpCloseButton',
    inheritAttrs: false,
    props: {
        /** Підпис для assistive tech (видимого тексту немає). */
        ariaLabel: {
            type: String,
            default: 'Закрити',
        },
        /**
         * modal — шапки модалок;
         * lightbox — перегляд зображення;
         * sidebar-mobile / sidebar-desktop — панель чату;
         * toast — рядок у RpToastStack.
         */
        variant: {
            type: String,
            default: 'modal',
            validator: (v) =>
                ['modal', 'lightbox', 'sidebar-mobile', 'sidebar-desktop', 'toast'].includes(v),
        },
    },
    computed: {
        buttonClass() {
            return VARIANTS[this.variant] || VARIANTS.modal;
        },
        iconClass() {
            return ICON_SIZES[this.variant] || ICON_SIZES.modal;
        },
    },
    methods: {
        /** Для ref на компоненті (фокус off-canvas тощо). */
        focus(opts) {
            if (this.$el && typeof this.$el.focus === 'function') {
                this.$el.focus(opts);
            }
        },
    },
};
</script>
