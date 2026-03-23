<template>
    <div class="shrink-0">
        <div class="rp-chat-toolbar rounded-none" role="toolbar" aria-label="Форматування та дії">
            <button
                type="button"
                class="rp-focusable rp-chat-toolbar-btn"
                :class="{ 'rp-chat-toolbar-btn--active': composerStyle.bg }"
                title="Колір тла повідомлення"
                aria-label="Колір тла повідомлення"
                :aria-expanded="formatPanel === 'bg' ? 'true' : 'false'"
                aria-haspopup="true"
                @click="$emit('toggle-format-panel', 'bg')"
            >
                <svg class="h-[18px] w-[18px]" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <circle cx="12" cy="12" r="8" />
                </svg>
            </button>
            <button
                type="button"
                class="rp-focusable rp-chat-toolbar-btn"
                :class="{ 'rp-chat-toolbar-btn--active': composerStyle.fg }"
                title="Колір тексту"
                aria-label="Колір тексту"
                :aria-expanded="formatPanel === 'fg' ? 'true' : 'false'"
                aria-haspopup="true"
                :disabled="Boolean(composerStyle.bg)"
                :aria-disabled="composerStyle.bg ? 'true' : 'false'"
                @click="$emit('toggle-format-panel', 'fg')"
            >
                <svg class="h-[18px] w-[18px]" aria-hidden="true" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="7.25" stroke="currentColor" stroke-width="2" />
                </svg>
            </button>
            <button
                type="button"
                class="rp-focusable rp-chat-toolbar-btn"
                :class="{ 'rp-chat-toolbar-btn--active': composerStyle.bold }"
                title="Напівжирний"
                aria-label="Напівжирний"
                :aria-pressed="composerStyle.bold ? 'true' : 'false'"
                @click="$emit('toggle-bold')"
            >
                <span class="text-sm font-bold" aria-hidden="true">B</span>
            </button>
            <button
                type="button"
                class="rp-focusable rp-chat-toolbar-btn"
                :class="{ 'rp-chat-toolbar-btn--active': composerStyle.italic }"
                title="Курсив"
                aria-label="Курсив"
                :aria-pressed="composerStyle.italic ? 'true' : 'false'"
                @click="$emit('toggle-italic')"
            >
                <span class="text-sm italic" aria-hidden="true">I</span>
            </button>
            <button
                type="button"
                class="rp-focusable rp-chat-toolbar-btn"
                :class="{ 'rp-chat-toolbar-btn--active': composerStyle.underline }"
                title="Підкреслення"
                aria-label="Підкреслення"
                :aria-pressed="composerStyle.underline ? 'true' : 'false'"
                @click="$emit('toggle-underline')"
            >
                <span class="text-sm underline" aria-hidden="true">U</span>
            </button>

            <span class="rp-chat-toolbar-spacer" aria-hidden="true" />

            <button
                type="button"
                class="rp-focusable rp-chat-toolbar-btn"
                :disabled="imageUploadBlocked || !selectedRoomId || uploadingImage || editPostId"
                title="Мої зображення"
                aria-label="Мої зображення"
                @click="$emit('open-my-images')"
            >
                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
                    />
                </svg>
            </button>
            <router-link
                :to="archiveRoute"
                class="rp-focusable rp-chat-toolbar-btn"
                title="Архів чату"
            >
                <span class="rp-sr-only">Архів чату</span>
                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"
                    />
                </svg>
            </router-link>
            <button
                type="button"
                class="rp-focusable rp-chat-toolbar-btn"
                title="Вийти з чату"
                :disabled="loggingOut"
                @click="$emit('logout')"
            >
                <span class="rp-sr-only">Вийти</span>
                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.59L17 17l5-5-5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"
                    />
                </svg>
            </button>
        </div>
        <div
            v-if="formatPanel === 'bg'"
            class="rp-chat-fmt-palette flex flex-wrap items-center gap-2 border-t border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] px-2 py-2 sm:px-3"
            role="group"
            aria-label="Палітра тла повідомлення"
        >
            <button
                type="button"
                class="rp-focusable rounded border border-[var(--rp-chat-chrome-border)] px-2 py-1 text-[0.7rem] text-[var(--rp-text)]"
                @click="$emit('clear-bg')"
            >
                Без тла
            </button>
            <button
                v-for="opt in composerBgPalette"
                :key="opt.key"
                type="button"
                class="rp-focusable rp-chat-fmt-swatch h-7 w-7 rounded border border-[var(--rp-chat-chrome-border)] shadow-sm"
                :class="'rp-chat-fmt-swatch--' + opt.key"
                :title="opt.label"
                :aria-label="opt.label"
                @click="$emit('set-bg', opt.key)"
            />
        </div>
        <div
            v-if="formatPanel === 'fg'"
            class="rp-chat-fmt-palette flex flex-wrap items-center gap-2 border-t border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-toolbar-bg)] px-2 py-2 sm:px-3"
            role="group"
            aria-label="Палітра кольору тексту"
        >
            <button
                type="button"
                class="rp-focusable rounded border border-[var(--rp-chat-chrome-border)] px-2 py-1 text-[0.7rem] text-[var(--rp-text)]"
                @click="$emit('clear-fg')"
            >
                Звичайний колір
            </button>
            <button
                v-for="opt in composerFgPalette"
                :key="opt.key"
                type="button"
                class="rp-focusable rp-chat-fmt-swatch h-7 w-7 rounded-full border-2 border-[var(--rp-chat-chrome-border)]"
                :class="'rp-chat-fmt-swatch-fg--' + opt.key"
                :title="opt.label"
                :aria-label="opt.label"
                @click="$emit('set-fg', opt.key)"
            />
        </div>
    </div>
</template>

<script>
export default {
    name: 'ChatRoomComposerToolbar',
    props: {
        formatPanel: { type: String, default: null },
        composerStyle: { type: Object, required: true },
        composerBgPalette: { type: Array, required: true },
        composerFgPalette: { type: Array, required: true },
        imageUploadBlocked: { type: Boolean, default: false },
        selectedRoomId: {
            default: null,
            validator: (v) => v === null || v === undefined || typeof v === 'number',
        },
        uploadingImage: { type: Boolean, default: false },
        editPostId: { default: null, validator: (v) => v === null || v === undefined || typeof v === 'number' },
        loggingOut: { type: Boolean, default: false },
        archiveRoute: { type: Object, required: true },
    },
};
</script>
