<template>
    <div>
        <!-- Мобільне бургер-меню: X зліва, вкладки іконками справа (референс) -->
        <div
            class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-chat-sidebar-border)] px-2 py-3 md:hidden"
        >
            <RpCloseButton
                ref="panelCloseBtnMobile"
                variant="sidebar-mobile"
                aria-label="Закрити панель"
                @click="$emit('close')"
            />
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
                    class="rp-focusable relative flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-lg text-[var(--rp-chat-sidebar-fg)]"
                    :class="
                        sidebarTab === tab.id
                            ? 'bg-[var(--rp-chat-sidebar-tab-active-bg)] ring-1 ring-[color-mix(in_srgb,var(--rp-chat-sidebar-fg)_22%,transparent)]'
                            : 'bg-transparent hover:bg-[var(--rp-chat-sidebar-tab-active-bg)]'
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
                    <span
                        v-if="tab.id === 'friends' && friendsIncomingPendingCount > 0"
                        class="pointer-events-none absolute -right-0.5 -top-0.5 flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-0.5 text-[10px] font-bold leading-none text-white shadow ring-1 ring-black/20"
                        aria-hidden="true"
                    >{{ friendsIncomingBadgeText }}</span>
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
                    class="rp-focusable relative flex h-11 min-w-0 flex-1 cursor-pointer items-center justify-center rounded-md border-2 text-[var(--rp-chat-sidebar-icon)]"
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
                    <span
                        v-if="tab.id === 'friends' && friendsIncomingPendingCount > 0"
                        class="pointer-events-none absolute right-1 top-0.5 flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-0.5 text-[10px] font-bold leading-none text-white shadow ring-1 ring-black/15"
                        aria-hidden="true"
                    >{{ friendsIncomingBadgeText }}</span>
                </button>
            </div>
            <RpCloseButton
                ref="panelCloseBtnDesktop"
                variant="sidebar-desktop"
                aria-label="Закрити панель"
                @click="$emit('close')"
            />
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
        /** T202: вхідні pending-запити на дружбу (той самий облік, що friendsIncoming). */
        friendsIncomingPendingCount: { type: Number, default: 0 },
    },
    computed: {
        privateUnreadBadgeText() {
            const n = this.privateUnreadTotal;
            if (!n) {
                return '';
            }

            return n > 99 ? '99+' : String(n);
        },
        friendsIncomingBadgeText() {
            const n = this.friendsIncomingPendingCount;
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
            if (tab.id === 'private' && this.privateUnreadTotal) {
                const n = this.privateUnreadTotal;
                if (n === 1) {
                    return 'Приват, 1 непрочитане повідомлення';
                }
                const display = n > 99 ? '99+' : String(n);

                return `Приват, ${display} непрочитаних повідомлень`;
            }
            if (tab.id === 'friends' && this.friendsIncomingPendingCount) {
                const n = this.friendsIncomingPendingCount;
                if (n === 1) {
                    return 'Друзі, 1 вхідний запит на дружбу';
                }
                const display = n > 99 ? '99+' : String(n);

                return `Друзі, ${display} вхідних запитів на дружбу`;
            }

            return tab.title;
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
