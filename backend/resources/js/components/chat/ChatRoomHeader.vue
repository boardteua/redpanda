<template>
    <header class="mb-2 flex w-full flex-shrink-0 flex-col gap-1.5 px-2 py-1 sm:px-3">
        <div v-if="chatBreadcrumb" class="min-w-0">
            <p
                class="truncate text-[0.6875rem] font-semibold tracking-wide text-[var(--rp-text-muted)]"
            >
                {{ chatBreadcrumb }}
            </p>
        </div>
        <div class="flex min-w-0 flex-nowrap items-center justify-between gap-2">
            <div class="min-w-0 flex-1">
                <p
                    v-if="chatTopicLine"
                    class="truncate text-sm leading-snug text-[var(--rp-text-muted)]"
                    :title="chatTopicLine"
                >
                    {{ chatTopicLine }}
                </p>
            </div>
            <div class="flex shrink-0 flex-nowrap items-center justify-end gap-2">
                <span
                    v-if="wsDegraded"
                    class="max-w-[11rem] rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-chat-toolbar-bg)] px-2 py-1 text-xs text-[var(--rp-text-muted)] md:max-w-none"
                    role="status"
                >
                    Реалтайм недоступний — оновлення через опитування
                </span>
                <button
                    v-if="!panelOpen"
                    ref="mobilePanelToggle"
                    type="button"
                    class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] text-[var(--rp-text-muted)] hover:bg-[var(--rp-chat-composer-bg)] md:hidden"
                    aria-expanded="false"
                    aria-controls="chat-panel"
                    title="Меню"
                    @click="$emit('toggle-panel')"
                >
                    <span class="rp-sr-only">Відкрити або сховати меню чату</span>
                    <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z" />
                    </svg>
                </button>
                <button
                    v-if="!panelOpen"
                    ref="desktopPanelToggle"
                    type="button"
                    class="rp-focusable hidden h-11 w-11 shrink-0 items-center justify-center rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] text-[var(--rp-text-muted)] hover:bg-[var(--rp-chat-composer-bg)] md:inline-flex"
                    aria-expanded="false"
                    aria-controls="chat-panel"
                    title="Панель чату"
                    @click="$emit('toggle-panel')"
                >
                    <span class="rp-sr-only">Перемкнути панель чату</span>
                    <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
                        />
                    </svg>
                </button>
            </div>
        </div>
    </header>
</template>

<script>
export default {
    name: 'ChatRoomHeader',
    props: {
        chatBreadcrumb: { type: String, default: '' },
        chatTopicLine: { type: String, default: '' },
        panelOpen: { type: Boolean, default: false },
        wsDegraded: { type: Boolean, default: false },
    },
};
</script>
