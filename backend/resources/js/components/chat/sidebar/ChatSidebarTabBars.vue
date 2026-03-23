<template>
    <div>
        <!-- Мобільне бургер-меню: X зліва, вкладки іконками справа (референс) -->
        <div
            class="flex shrink-0 items-center justify-between gap-2 border-b border-white/10 px-2 py-3 md:hidden"
        >
            <button
                ref="panelCloseBtnMobile"
                type="button"
                class="rp-focusable flex h-12 w-12 shrink-0 items-center justify-center rounded-lg text-white hover:bg-white/10"
                aria-label="Закрити панель"
                @click="$emit('close')"
            >
                <svg class="h-9 w-9" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                    />
                </svg>
            </button>
            <div
                class="flex min-w-0 flex-1 justify-end gap-1 overflow-x-auto [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                role="tablist"
                aria-label="Вкладки панелі чату"
                @keydown="onTabKeydown"
            >
                <button
                    v-for="tab in sidebarTabs"
                    :key="'m-' + tab.id"
                    :id="'chat-tab-m-' + tab.id"
                    type="button"
                    role="tab"
                    class="rp-focusable relative flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-white/95"
                    :class="
                        sidebarTab === tab.id
                            ? 'bg-white/20 ring-1 ring-white/35'
                            : 'bg-white/5 hover:bg-white/12'
                    "
                    :aria-selected="sidebarTab === tab.id ? 'true' : 'false'"
                    :aria-controls="'chat-panel-' + tab.id"
                    :tabindex="sidebarTab === tab.id ? 0 : -1"
                    :title="sidebarTabTitle(tab)"
                    :aria-label="sidebarTabAriaLabel(tab)"
                    @click="$emit('select-tab', tab.id)"
                >
                    <span class="inline-flex [&_svg]:h-6 [&_svg]:w-6" aria-hidden="true" v-html="tab.icon" />
                    <span
                        v-if="tab.id === 'private' && privateUnreadTotal > 0"
                        class="pointer-events-none absolute -right-0.5 -top-0.5 flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-0.5 text-[10px] font-bold leading-none text-white shadow ring-1 ring-black/20"
                        aria-hidden="true"
                    >{{ privateUnreadBadgeText }}</span>
                </button>
            </div>
        </div>

        <!-- Десктоп: вкладки в верхній смузі поруч із закриттям (без заголовка «Панель») -->
        <div
            class="hidden shrink-0 items-center gap-1 border-b border-[var(--rp-chat-sidebar-border)] px-1 py-2 md:flex"
        >
            <div
                class="flex min-w-0 flex-1 gap-1"
                role="tablist"
                aria-label="Вкладки панелі чату"
                @keydown="onTabKeydown"
            >
                <button
                    v-for="tab in sidebarTabs"
                    :key="'d-' + tab.id"
                    :id="'chat-tab-d-' + tab.id"
                    type="button"
                    role="tab"
                    class="rp-focusable relative flex h-11 min-w-0 flex-1 items-center justify-center rounded-md border-2 text-[var(--rp-chat-sidebar-icon)]"
                    :class="
                        sidebarTab === tab.id
                            ? 'border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-tab-active-bg)] text-[var(--rp-chat-sidebar-fg)]'
                            : 'border-transparent bg-transparent hover:bg-[var(--rp-chat-sidebar-tab-active-bg)]'
                    "
                    :aria-selected="sidebarTab === tab.id ? 'true' : 'false'"
                    :aria-controls="'chat-panel-' + tab.id"
                    :tabindex="sidebarTab === tab.id ? 0 : -1"
                    :title="sidebarTabTitle(tab)"
                    :aria-label="sidebarTabAriaLabel(tab)"
                    @click="$emit('select-tab', tab.id)"
                >
                    <span class="inline-flex items-center justify-center" aria-hidden="true" v-html="tab.icon" />
                    <span
                        v-if="tab.id === 'private' && privateUnreadTotal > 0"
                        class="pointer-events-none absolute right-1 top-0.5 flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-0.5 text-[10px] font-bold leading-none text-white shadow ring-1 ring-black/15"
                        aria-hidden="true"
                    >{{ privateUnreadBadgeText }}</span>
                </button>
            </div>
            <button
                ref="panelCloseBtnDesktop"
                type="button"
                class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-[var(--rp-chat-sidebar-icon)] hover:bg-[var(--rp-chat-sidebar-tab-active-bg)] hover:text-[var(--rp-chat-sidebar-fg)]"
                aria-label="Закрити панель"
                @click="$emit('close')"
            >
                <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                    />
                </svg>
            </button>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ChatSidebarTabBars',
    props: {
        sidebarTabs: { type: Array, required: true },
        sidebarTab: { type: String, required: true },
        privateUnreadTotal: { type: Number, default: 0 },
    },
    computed: {
        privateUnreadBadgeText() {
            const n = this.privateUnreadTotal;
            if (!n) {
                return '';
            }

            return n > 99 ? '99+' : String(n);
        },
    },
    methods: {
        sidebarTabTitle(tab) {
            return tab && tab.title ? tab.title : '';
        },
        sidebarTabAriaLabel(tab) {
            if (!tab) {
                return '';
            }
            if (tab.id !== 'private' || !this.privateUnreadTotal) {
                return tab.title;
            }
            const n = this.privateUnreadTotal;
            if (n === 1) {
                return 'Приват, 1 непрочитане повідомлення';
            }
            const display = n > 99 ? '99+' : String(n);

            return `Приват, ${display} непрочитаних повідомлень`;
        },
        onTabKeydown(e) {
            const ids = this.sidebarTabs.map((t) => t.id);
            const i = ids.indexOf(this.sidebarTab);
            if (i < 0) {
                return;
            }
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                const delta = e.key === 'ArrowRight' ? 1 : -1;
                const next = ids[(i + delta + ids.length) % ids.length];
                this.$emit('select-tab', next);
            }
            if (e.key === 'Home') {
                e.preventDefault();
                this.$emit('select-tab', ids[0]);
            }
            if (e.key === 'End') {
                e.preventDefault();
                this.$emit('select-tab', ids[ids.length - 1]);
            }
        },
    },
};
</script>
