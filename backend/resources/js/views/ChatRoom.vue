<template>
    <div
        class="flex min-h-screen flex-col bg-[var(--rp-bg)] md:h-[100dvh] md:max-h-screen md:flex-row md:overflow-hidden md:p-0"
    >
        <!-- Затемнення (лише мобільний off-canvas) -->
        <button
            v-if="panelOpen && isNarrowViewport"
            type="button"
            class="rp-focusable fixed inset-0 z-40 bg-black/55 md:hidden"
            aria-label="Закрити панель чату"
            @click="closePanel"
        />

        <div
            class="rp-chat-external-wrap min-h-0 min-w-0 max-md:flex max-md:flex-1 max-md:flex-col md:min-h-0 md:flex-1"
        >
            <div
                class="flex min-h-0 min-w-0 flex-1 flex-col bg-[var(--rp-chat-app-bg)] px-3 py-2 md:px-0 md:py-0 md:min-h-0 md:overflow-hidden"
            >
            <header
                class="mb-2 flex w-full flex-shrink-0 flex-col gap-1 border-b border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-header-bg)] px-2 py-2 sm:px-3"
            >
                <div v-if="chatBreadcrumb || chatTopicLine" class="min-w-0">
                    <p
                        v-if="chatBreadcrumb"
                        class="truncate text-[0.6875rem] font-semibold tracking-wide text-[var(--rp-text)]"
                    >
                        {{ chatBreadcrumb }}
                    </p>
                    <p
                        v-if="chatTopicLine"
                        class="truncate text-[0.625rem] text-[var(--rp-text-muted)]"
                    >
                        {{ chatTopicLine }}
                    </p>
                </div>
                <div class="flex min-w-0 flex-wrap items-center justify-between gap-2">
                <div class="flex min-w-0 flex-wrap items-center gap-3">
                    <button
                        type="button"
                        class="rp-focusable shrink-0 text-sm font-medium text-[var(--rp-link)] hover:text-[var(--rp-link-hover)]"
                        :disabled="loggingOut"
                        @click="logout"
                    >
                        Вийти
                    </button>
                    <router-link
                        :to="{
                            name: 'archive',
                            query: selectedRoomId ? { room: String(selectedRoomId) } : {},
                        }"
                        class="rp-focusable shrink-0 text-sm font-medium text-[var(--rp-link)] hover:text-[var(--rp-link-hover)]"
                    >
                        Архів чату
                    </router-link>
                    <button
                        ref="mobilePanelToggle"
                        type="button"
                        class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md border-2 border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] text-[var(--rp-text)] md:hidden"
                        :aria-expanded="panelOpen ? 'true' : 'false'"
                        aria-controls="chat-panel"
                        title="Меню"
                        @click="togglePanel"
                    >
                        <span class="rp-sr-only">Відкрити або сховати меню чату</span>
                        <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z" />
                        </svg>
                    </button>
                    <span
                        v-if="wsDegraded"
                        class="rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-surface-elevated)] px-2 py-1 text-xs text-[var(--rp-text-muted)]"
                        role="status"
                    >
                        Реалтайм недоступний — оновлення через опитування
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        ref="desktopPanelToggle"
                        type="button"
                        class="rp-focusable hidden h-11 w-11 items-center justify-center rounded-md border-2 border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] text-[var(--rp-text)] md:inline-flex"
                        :aria-expanded="panelOpen ? 'true' : 'false'"
                        aria-controls="chat-panel"
                        title="Панель чату"
                        @click="togglePanel"
                    >
                        <span class="rp-sr-only">Перемкнути панель чату</span>
                        <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
                            />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-ghost text-sm"
                        aria-label="Перемкнути тему оформлення"
                        @click="cycleTheme"
                    >
                        {{ themeLabel }}
                    </button>
                </div>
                </div>
            </header>

            <p
                v-if="logoutError"
                class="mb-2 text-sm text-[var(--rp-error)]"
                role="alert"
                aria-live="polite"
            >
                {{ logoutError }}
            </p>

            <main
                id="main-content"
                class="flex min-h-0 w-full flex-1 flex-col gap-3 overflow-hidden pt-0"
                tabindex="-1"
            >
                <div v-if="loadError" class="rp-banner shrink-0" role="alert">
                    {{ loadError }}
                </div>
                <div
                    v-else-if="!loadingRooms && rooms.length === 0"
                    class="rp-banner shrink-0"
                    role="status"
                >
                    Немає доступних кімнат. Зверніться до адміністратора.
                </div>

                <div
                    v-else
                    class="flex min-h-0 flex-1 flex-col overflow-hidden border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-feed-bg)] md:border-0 md:shadow-none"
                >
                    <h2 class="rp-sr-only">Повідомлення</h2>
                    <div class="rp-chat-feed-wash flex min-h-0 flex-1 flex-col overflow-hidden">
                        <ul
                            ref="messageList"
                            class="flex min-h-0 flex-1 flex-col overflow-y-auto py-1"
                            role="log"
                            aria-live="polite"
                            aria-relevant="additions"
                        >
                            <li
                                v-for="(m, msgIdx) in messages"
                                :key="m.post_id"
                                class="flex gap-2 px-2 py-1.5 text-[0.9375rem] leading-snug sm:px-3 sm:py-2"
                                :class="[
                                    msgIdx % 2 === 0
                                        ? 'bg-[var(--rp-chat-row-even)]'
                                        : 'bg-[var(--rp-chat-row-odd)]',
                                    m.type === 'inline_private' ? 'rp-chat-feed-row--inline-private' : '',
                                ]"
                            >
                                <button
                                    v-if="user && m.post_user !== user.user_name"
                                    type="button"
                                    class="rp-focusable h-fit shrink-0 rounded-full border-0 bg-transparent p-0"
                                    :aria-label="'Приват у полі вводу: ' + m.post_user"
                                    @click.stop="insertFeedInlinePrivatePrefix(m.post_user)"
                                >
                                    <UserAvatar
                                        :src="m.avatar"
                                        :name="m.post_user"
                                        variant="feed"
                                        decorative
                                    />
                                </button>
                                <UserAvatar
                                    v-else
                                    :src="m.avatar"
                                    :name="m.post_user"
                                    variant="feed"
                                    decorative
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                                        <p class="min-w-0 flex-1 leading-snug text-[var(--rp-text)]">
                                            <button
                                                v-if="user"
                                                type="button"
                                                class="rp-focusable mr-1.5 inline font-semibold hover:underline"
                                                :style="nickColorStyle(m)"
                                                :aria-label="'Згадати у полі вводу: ' + m.post_user"
                                                @click.stop="insertFeedReplyPrefix(m.post_user)"
                                            >
                                                {{ m.post_user }}
                                            </button>
                                            <span
                                                v-else
                                                class="mr-1.5 inline font-semibold"
                                                :style="nickColorStyle(m)"
                                            >
                                                {{ m.post_user }}
                                            </span>
                                            <span
                                                v-if="m.post_message"
                                                class="whitespace-pre-wrap break-words"
                                            >
                                                {{ m.post_message }}
                                            </span>
                                        </p>
                                        <time
                                            class="shrink-0 font-mono text-[0.6875rem] tabular-nums text-[var(--rp-text-muted)]"
                                        >
                                            {{ m.post_time || '—' }}
                                        </time>
                                    </div>
                                    <figure v-if="m.image && m.image.url" class="mt-1.5">
                                        <img
                                            :src="m.image.url"
                                            alt="Вкладене зображення"
                                            class="max-h-64 max-w-full rounded-md border border-[var(--rp-chat-chrome-border)] object-contain"
                                            loading="lazy"
                                        />
                                    </figure>
                                </div>
                            </li>
                        </ul>
                        <p
                            v-if="messages.length === 0 && !loadingMessages"
                            class="p-4 text-center text-sm text-[var(--rp-text-muted)]"
                        >
                            Ще немає повідомлень. Напишіть перше нижче.
                        </p>
                    </div>

                    <form
                        class="flex shrink-0 flex-col border-t border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-composer-bg)]"
                        @submit.prevent="sendMessage"
                    >
                        <div class="rp-chat-toolbar rounded-none" role="toolbar" aria-label="Форматування та дії">
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Колір тла повідомлення (згодом)"
                                aria-disabled="true"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M6 4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2H6zm0 2h12v12H6V6zm2 2v8h8V8H8z"
                                    />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Колір тексту (згодом)"
                                aria-disabled="true"
                            >
                                <span class="text-sm font-bold" aria-hidden="true">A</span>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Напівжирний (згодом)"
                                aria-disabled="true"
                            >
                                <span class="text-sm font-bold" aria-hidden="true">B</span>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Курсив (згодом)"
                                aria-disabled="true"
                            >
                                <span class="text-sm italic" aria-hidden="true">I</span>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Підкреслення (згодом)"
                                aria-disabled="true"
                            >
                                <span class="text-sm underline" aria-hidden="true">U</span>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Закреслення (згодом)"
                                aria-disabled="true"
                            >
                                <span class="text-sm line-through" aria-hidden="true">S</span>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Група (згодом)"
                                aria-disabled="true"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
                                    />
                                </svg>
                            </button>
                            <span class="rp-chat-toolbar-spacer" aria-hidden="true" />
                            <router-link
                                :to="{
                                    name: 'archive',
                                    query: selectedRoomId ? { room: String(selectedRoomId) } : {},
                                }"
                                class="rp-focusable rp-chat-toolbar-btn"
                                title="Архів чату"
                            >
                                <span class="rp-sr-only">Архів чату</span>
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M20.54 5.23l-1.39-1.68C18.88 3.21 18.47 3 18 3H6c-.47 0-.88.21-1.16.55L3.46 5.23C3.17 5.57 3 6.02 3 6.5V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6.5c0-.48-.17-.93-.46-1.27zM12 17.5L6.5 12H10v-2h4v2h3.5L12 17.5zM5.12 5l.81-1h12l.94 1H5.12z"
                                    />
                                </svg>
                            </router-link>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Мої зображення (згодом)"
                                aria-disabled="true"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
                                    />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Чат-рулетка (згодом)"
                                aria-disabled="true"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM7.5 18c-.83 0-1.5-.67-1.5-1.5S6.67 15 7.5 15s1.5.67 1.5 1.5S8.33 18 7.5 18zm0-9C6.67 9 6 8.33 6 7.5S6.67 6 7.5 6 9 6.67 9 7.5 8.33 9 7.5 9zm5 4.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm5 4.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm0-9c-.83 0-1.5-.67-1.5-1.5S16.67 6 17.5 6s1.5.67 1.5 1.5S18.33 9 17.5 9z"
                                    />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                title="Відкрити панель чату"
                                @click="beginOpeningPanel"
                            >
                                <span class="rp-sr-only">Відкрити панель чату</span>
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <label class="rp-sr-only" for="chat-composer">Повідомлення</label>
                        <div class="rp-chat-composer-row">
                            <button
                                type="button"
                                class="rp-focusable rp-chat-composer-rail-btn"
                                disabled
                                title="Смайли (згодом, T33)"
                                aria-label="Смайли"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"
                                    />
                                </svg>
                            </button>
                            <div class="rp-chat-composer-slot min-w-0 flex-[1_1_12rem]">
                                <textarea
                                    id="chat-composer"
                                    ref="chatComposer"
                                    v-model="composerText"
                                    class="rp-focusable rp-chat-composer-input w-full resize-y"
                                    maxlength="4000"
                                    rows="3"
                                    :disabled="sending || uploadingImage || !selectedRoomId"
                                    placeholder="Текст повідомлення… Enter — надіслати, Shift+Enter — новий рядок"
                                    @keydown="onChatComposerKeydown"
                                />
                            </div>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-composer-rail-btn"
                                :disabled="sending || uploadingImage || !selectedRoomId"
                                title="Додати зображення (JPEG, PNG, GIF, WebP, до 4 МБ)"
                                aria-label="Додати зображення до повідомлення"
                                @click="$refs.imageInput && $refs.imageInput.click()"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"
                                    />
                                </svg>
                            </button>
                            <button
                                type="submit"
                                class="rp-focusable rp-chat-send-primary"
                                :disabled="
                                    sending
                                    || uploadingImage
                                    || !selectedRoomId
                                    || (!composerText.trim() && !pendingImageId)
                                "
                                title="Надіслати повідомлення"
                                aria-label="Надіслати повідомлення"
                            >
                                <svg class="h-5 w-5 shrink-0" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                </svg>
                                <span class="rp-chat-send-primary__label" aria-hidden="true">Надіслати</span>
                            </button>
                        </div>
                        <p class="px-2 pb-1 text-[0.7rem] text-[var(--rp-text-muted)] sm:px-3">
                            Приват: <code class="rounded bg-[var(--rp-chat-toolbar-bg)] px-1 font-mono text-[0.65rem]">/msg</code> нік текст.
                        </p>
                        <input
                            ref="imageInput"
                            type="file"
                            class="hidden"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            @change="onChatImageSelected"
                        />
                        <div
                            v-if="pendingImageId && pendingPreviewUrl"
                            class="mx-2 mb-2 flex flex-wrap items-center gap-3 rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-row-even)] p-2 sm:mx-3"
                        >
                            <img
                                :src="pendingPreviewUrl"
                                alt=""
                                class="max-h-24 max-w-[12rem] rounded object-contain"
                            />
                            <button
                                type="button"
                                class="rp-focusable rp-btn rp-btn-ghost text-sm"
                                :disabled="sending || uploadingImage"
                                @click="clearPendingChatImage"
                            >
                                Прибрати фото
                            </button>
                        </div>
                        <p
                            v-if="imageUploadError"
                            class="mx-2 mb-2 text-sm text-[var(--rp-error)] sm:mx-3"
                            role="alert"
                        >
                            {{ imageUploadError }}
                        </p>
                    </form>
                </div>
            </main>
        </div>

        <!-- Панель #chat_panel — 320px, порядок вкладок як у CHAT-PANEL-SIDEBAR -->
        <aside
            id="chat-panel"
            class="rp-chat-sidebar rp-chat-burger-drawer flex w-[320px] max-w-[100vw] flex-shrink-0 flex-col border-l border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-bg)] text-[var(--rp-chat-sidebar-fg)] max-md:fixed max-md:inset-y-0 max-md:right-0 max-md:z-50 max-md:shadow-2xl max-md:transition-transform max-md:duration-200 max-md:ease-out md:relative md:z-auto md:min-h-0 md:self-stretch md:max-w-[320px] md:shadow-none md:transition-none"
            :class="[
                isNarrowViewport && (panelOpen ? 'max-md:translate-x-0' : 'max-md:translate-x-full'),
                !isNarrowViewport && !panelOpen ? 'md:hidden' : '',
            ]"
            aria-label="Панель чату"
        >
            <!-- Мобільне бургер-меню: X зліва, вкладки іконками справа (референс) -->
            <div
                class="flex shrink-0 items-center justify-between gap-2 border-b border-white/10 px-2 py-3 md:hidden"
            >
                <button
                    ref="panelCloseBtnMobile"
                    type="button"
                    class="rp-focusable flex h-12 w-12 shrink-0 items-center justify-center rounded-lg text-white hover:bg-white/10"
                    aria-label="Закрити панель"
                    @click="closePanel"
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
                    @keydown="onSidebarTabKeydown"
                >
                    <button
                        v-for="tab in sidebarTabs"
                        :key="'m-' + tab.id"
                        :id="'chat-tab-m-' + tab.id"
                        type="button"
                        role="tab"
                        class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-lg text-white/95"
                        :class="
                            sidebarTab === tab.id
                                ? 'bg-white/20 ring-1 ring-white/35'
                                : 'bg-white/5 hover:bg-white/12'
                        "
                        :aria-selected="sidebarTab === tab.id ? 'true' : 'false'"
                        :aria-controls="'chat-panel-' + tab.id"
                        :tabindex="sidebarTab === tab.id ? 0 : -1"
                        :title="tab.title"
                        @click="selectSidebarTab(tab.id)"
                    >
                        <span class="rp-sr-only">{{ tab.title }}</span>
                        <span class="inline-flex [&_svg]:h-6 [&_svg]:w-6" v-html="tab.icon" />
                    </button>
                </div>
            </div>

            <!-- Десктоп: заголовок + закриття -->
            <div
                class="hidden shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-chat-sidebar-border)] px-3 py-2 md:flex"
            >
                <h2 class="text-sm font-semibold text-[var(--rp-chat-sidebar-fg)]">Панель</h2>
                <button
                    ref="panelCloseBtnDesktop"
                    type="button"
                    class="rp-focusable flex h-11 w-11 items-center justify-center rounded-md text-[var(--rp-chat-sidebar-icon)] hover:bg-[var(--rp-chat-sidebar-tab-active-bg)] hover:text-[var(--rp-chat-sidebar-fg)]"
                    aria-label="Закрити панель"
                    @click="closePanel"
                >
                    <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                        />
                    </svg>
                </button>
            </div>

            <div
                class="hidden shrink-0 border-b border-[var(--rp-chat-sidebar-border)] px-1 py-2 md:flex"
                role="tablist"
                aria-label="Вкладки панелі чату"
                @keydown="onSidebarTabKeydown"
            >
                <button
                    v-for="tab in sidebarTabs"
                    :key="'d-' + tab.id"
                    :id="'chat-tab-d-' + tab.id"
                    type="button"
                    role="tab"
                    class="rp-focusable flex h-11 flex-1 items-center justify-center rounded-md border-2 text-[var(--rp-chat-sidebar-icon)]"
                    :class="
                        sidebarTab === tab.id
                            ? 'border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-tab-active-bg)] text-[var(--rp-chat-sidebar-fg)]'
                            : 'border-transparent bg-transparent hover:bg-[var(--rp-chat-sidebar-tab-active-bg)]'
                    "
                    :aria-selected="sidebarTab === tab.id ? 'true' : 'false'"
                    :aria-controls="'chat-panel-' + tab.id"
                    :tabindex="sidebarTab === tab.id ? 0 : -1"
                    :title="tab.title"
                    @click="selectSidebarTab(tab.id)"
                >
                    <span class="rp-sr-only">{{ tab.title }}</span>
                    <span class="inline-flex items-center justify-center" v-html="tab.icon" />
                </button>
            </div>

            <div
                class="rp-chat-burger-scroll min-h-0 flex-1 overflow-y-auto p-3 text-sm text-[var(--rp-chat-sidebar-fg)]"
            >
                <div
                    v-if="privateListLoadError || friendsIgnoresLoadError"
                    class="mb-3 space-y-2"
                    role="region"
                    aria-label="Помилки завантаження списків"
                >
                    <p
                        v-if="privateListLoadError"
                        role="alert"
                        class="rounded-md border border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-xs text-[var(--rp-error)]"
                    >
                        {{ privateListLoadError }}
                    </p>
                    <p
                        v-if="friendsIgnoresLoadError"
                        role="alert"
                        class="rounded-md border border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-xs text-[var(--rp-error)]"
                    >
                        {{ friendsIgnoresLoadError }}
                    </p>
                </div>
                <!-- Люди -->
                <div
                    v-show="sidebarTab === 'users'"
                    id="chat-panel-users"
                    role="tabpanel"
                    :aria-labelledby="panelTabLabelledby('users')"
                    tabindex="-1"
                    :aria-hidden="sidebarTab === 'users' ? 'false' : 'true'"
                >
                    <!-- Мобільний рядок «я» + інлайн-сендвіч (жовтий блок під рядком, як у референсі board.te.ua) -->
                    <div v-if="user" class="mb-4 md:hidden">
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center gap-1">
                                <div
                                    class="flex min-w-0 flex-1 items-center gap-3 rounded-lg px-3 py-2.5"
                                    style="background: var(--rp-burger-self-bar-bg)"
                                >
                                    <UserAvatar
                                        :src="user.avatar_url || ''"
                                        :name="user.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                    <span class="min-w-0 truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                        user.user_name
                                    }}</span>
                                </div>
                                <SidebarHamburgerTrigger
                                    :expanded="sidebarBadgeMenuOpen('self-m')"
                                    aria-label="Меню дій для вашого профілю"
                                    @activate="openSelfBadgeMenu($event, 'self-m')"
                                />
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="sidebarBadgeMenuOpen('self-m') && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="null"
                                @pick="onSidebarBadgeMenuPick"
                                @close="closeSidebarBadgeMenu"
                            />
                        </div>
                    </div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">
                        Онлайн
                    </p>
                    <ul v-if="user" class="space-y-2">
                        <li class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2">
                            <div class="flex items-center gap-1">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <UserAvatar
                                        :src="user.avatar_url || ''"
                                        :name="user.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                    <span
                                        v-if="user.badge_color"
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :style="{ backgroundColor: user.badge_color }"
                                        :title="user.chat_role || ''"
                                        aria-hidden="true"
                                    />
                                    <span class="font-medium text-[var(--rp-chat-sidebar-fg)]">{{ user.user_name }}</span>
                                    <span class="text-xs text-[var(--rp-chat-sidebar-muted)]">(ви)</span>
                                </div>
                                <SidebarHamburgerTrigger
                                    :expanded="sidebarBadgeMenuOpen('self-d')"
                                    aria-label="Меню дій для вашого профілю"
                                    @activate="openSelfBadgeMenu($event, 'self-d')"
                                />
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="sidebarBadgeMenuOpen('self-d') && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="null"
                                @pick="onSidebarBadgeMenuPick"
                                @close="closeSidebarBadgeMenu"
                            />
                        </li>
                    </ul>
                    <ul
                        v-if="roomPresencePeers.length > 0"
                        class="mt-3 space-y-2"
                        aria-label="Інші учасники онлайн"
                    >
                        <li
                            v-for="p in roomPresencePeers"
                            :key="'presence-' + p.id"
                            class="rp-chat-side-card flex flex-col rounded-md border px-2 py-2"
                        >
                            <div class="flex items-center gap-1">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <UserAvatar
                                        :src="p.avatar_url || ''"
                                        :name="p.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                    <span
                                        v-if="p.badge_color"
                                        class="h-2 w-2 shrink-0 rounded-full"
                                        :style="{ backgroundColor: p.badge_color }"
                                        :title="p.chat_role || ''"
                                        aria-hidden="true"
                                    />
                                    <span class="min-w-0 truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                        p.user_name
                                    }}</span>
                                    <span
                                        v-if="p.guest"
                                        class="shrink-0 text-xs text-[var(--rp-chat-sidebar-muted)]"
                                    >
                                        (гість)
                                    </span>
                                </div>
                                <SidebarHamburgerTrigger
                                    :expanded="sidebarBadgeMenuOpen(sidebarPresenceMenuRowKey(p))"
                                    :aria-label="'Меню дій для ' + p.user_name"
                                    @activate="openPeerBadgeMenu($event, p, sidebarPresenceMenuRowKey(p))"
                                />
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="sidebarBadgeMenuOpen(sidebarPresenceMenuRowKey(p)) && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="badgeMenu.target"
                                @pick="onSidebarBadgeMenuPick"
                                @close="closeSidebarBadgeMenu"
                            />
                        </li>
                    </ul>
                    <p
                        v-else-if="wsDegraded"
                        class="mt-3 text-xs text-[var(--rp-chat-sidebar-muted)]"
                    >
                        У режимі опитування список інших учасників онлайн недоступний.
                    </p>
                    <p v-else class="mt-3 text-xs text-[var(--rp-chat-sidebar-muted)]">
                        Нікого іншого онлайн у цій кімнаті.
                    </p>
                    <div class="mt-4 space-y-2 border-t border-[var(--rp-chat-sidebar-border)] pt-3">
                        <label class="rp-label" for="pm-lookup">Приват за ніком</label>
                        <div class="flex flex-wrap gap-2">
                            <input
                                id="pm-lookup"
                                v-model.trim="peerLookupName"
                                type="text"
                                maxlength="191"
                                class="rp-input rp-focusable min-w-[8rem] flex-1"
                                placeholder="нік"
                                @keyup.enter="lookupAndOpenPrivate"
                            />
                            <button
                                type="button"
                                class="rp-focusable rp-btn rp-btn-primary shrink-0"
                                :disabled="!peerLookupName"
                                @click="lookupAndOpenPrivate"
                            >
                                Відкрити
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Друзі -->
                <div
                    v-show="sidebarTab === 'friends'"
                    id="chat-panel-friends"
                    role="tabpanel"
                    :aria-labelledby="panelTabLabelledby('friends')"
                    tabindex="-1"
                    :aria-hidden="sidebarTab === 'friends' ? 'false' : 'true'"
                >
                    <div class="mb-3 flex gap-1">
                        <button
                            type="button"
                            class="rp-focusable rp-tab flex-1 px-1 text-xs sm:text-sm"
                            :aria-selected="friendsSubTab === 'active' ? 'true' : 'false'"
                            @click="friendsSubTab = 'active'"
                        >
                            Активний
                        </button>
                        <button
                            type="button"
                            class="rp-focusable rp-tab flex-1 px-1 text-xs sm:text-sm"
                            :aria-selected="friendsSubTab === 'pending' ? 'true' : 'false'"
                            @click="friendsSubTab = 'pending'"
                        >
                            Запити на дружбу
                        </button>
                    </div>
                    <template v-if="friendsSubTab === 'active'">
                        <p
                            v-if="friendsAccepted.length === 0"
                            class="text-center text-[var(--rp-chat-sidebar-muted)]"
                        >
                            Список друзів порожній.
                        </p>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="f in friendsAcceptedWithMenuPeer"
                                :key="f.user.id"
                                class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex min-w-0 flex-1 items-center gap-1">
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <UserAvatar
                                                :name="f.user.user_name"
                                                variant="sidebar"
                                                decorative
                                            />
                                            <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                                f.user.user_name
                                            }}</span>
                                        </div>
                                        <SidebarHamburgerTrigger
                                            :expanded="sidebarBadgeMenuOpen('friend-' + f.user.id)"
                                            :aria-label="'Меню дій для ' + f.user.user_name"
                                            @activate="openPeerBadgeMenu($event, f.menuPeer, 'friend-' + f.user.id)"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        class="rp-focusable rp-btn rp-btn-ghost shrink-0 text-sm"
                                        @click="openPrivatePeer(f.user)"
                                    >
                                        Приват
                                    </button>
                                </div>
                                <UserBadgeInlineActionPanel
                                    v-if="sidebarBadgeMenuOpen('friend-' + f.user.id) && user"
                                    :mode="badgeMenu.mode"
                                    :viewer="user"
                                    :target="badgeMenu.target"
                                    @pick="onSidebarBadgeMenuPick"
                                    @close="closeSidebarBadgeMenu"
                                />
                            </li>
                        </ul>
                    </template>
                    <template v-else>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">
                            Вхідні
                        </p>
                        <p
                            v-if="friendsIncoming.length === 0"
                            class="mb-4 text-center text-sm text-[var(--rp-chat-sidebar-muted)]"
                        >
                            Немає запитів у друзі
                        </p>
                        <ul v-else class="mb-4 space-y-2">
                            <li
                                v-for="r in friendsIncomingWithMenuPeer"
                                :key="'in-' + r.user.id"
                                class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
                            >
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="flex min-w-0 flex-1 basis-full items-center gap-1 sm:basis-auto">
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <UserAvatar
                                                :name="r.user.user_name"
                                                variant="sidebar"
                                                decorative
                                            />
                                            <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                                r.user.user_name
                                            }}</span>
                                        </div>
                                        <SidebarHamburgerTrigger
                                            :expanded="sidebarBadgeMenuOpen('fin-' + r.user.id)"
                                            :aria-label="'Меню дій для ' + r.user.user_name"
                                            @activate="openPeerBadgeMenu($event, r.menuPeer, 'fin-' + r.user.id)"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        class="rp-focusable rp-btn rp-btn-primary shrink-0 text-xs"
                                        @click="acceptFriend(r.user.id)"
                                    >
                                        Прийняти
                                    </button>
                                    <button
                                        type="button"
                                        class="rp-focusable rp-btn rp-btn-ghost shrink-0 text-xs"
                                        @click="rejectFriend(r.user.id)"
                                    >
                                        Відхилити
                                    </button>
                                </div>
                                <UserBadgeInlineActionPanel
                                    v-if="sidebarBadgeMenuOpen('fin-' + r.user.id) && user"
                                    :mode="badgeMenu.mode"
                                    :viewer="user"
                                    :target="badgeMenu.target"
                                    @pick="onSidebarBadgeMenuPick"
                                    @close="closeSidebarBadgeMenu"
                                />
                            </li>
                        </ul>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">
                            Вихідні
                        </p>
                        <p
                            v-if="friendsOutgoing.length === 0"
                            class="text-center text-sm text-[var(--rp-chat-sidebar-muted)]"
                        >
                            Немає відправлених запитів
                        </p>
                        <ul v-else class="space-y-1">
                            <li
                                v-for="r in friendsOutgoingWithMenuPeer"
                                :key="'out-' + r.user.id"
                                class="text-sm text-[var(--rp-chat-sidebar-fg)]"
                            >
                                <div class="flex flex-col gap-2 rounded-md py-1">
                                    <div class="flex items-center gap-1">
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <UserAvatar
                                                :name="r.user.user_name"
                                                variant="sidebar"
                                                decorative
                                            />
                                            <span class="truncate">{{ r.user.user_name }}</span>
                                        </div>
                                        <SidebarHamburgerTrigger
                                            :expanded="sidebarBadgeMenuOpen('fout-' + r.user.id)"
                                            :aria-label="'Меню дій для ' + r.user.user_name"
                                            @activate="openPeerBadgeMenu($event, r.menuPeer, 'fout-' + r.user.id)"
                                        />
                                    </div>
                                    <UserBadgeInlineActionPanel
                                        v-if="sidebarBadgeMenuOpen('fout-' + r.user.id) && user"
                                        :mode="badgeMenu.mode"
                                        :viewer="user"
                                        :target="badgeMenu.target"
                                        @pick="onSidebarBadgeMenuPick"
                                        @close="closeSidebarBadgeMenu"
                                    />
                                </div>
                            </li>
                        </ul>
                    </template>
                </div>

                <!-- Приват -->
                <div
                    v-show="sidebarTab === 'private'"
                    id="chat-panel-private"
                    role="tabpanel"
                    :aria-labelledby="panelTabLabelledby('private')"
                    tabindex="-1"
                    :aria-hidden="sidebarTab === 'private' ? 'false' : 'true'"
                >
                    <p
                        v-if="conversations.length === 0"
                        class="py-6 text-center text-[var(--rp-chat-sidebar-muted)]"
                    >
                        Немає нових повідомлень
                    </p>
                    <ul v-else class="space-y-2">
                        <li v-for="row in privateConversationRows" :key="row.key">
                            <div
                                v-if="row.menuPeer"
                                class="rp-chat-side-room-btn flex w-full items-stretch gap-2 rounded-md border-2 px-3 py-2"
                            >
                                <div class="flex shrink-0 items-start pt-0.5">
                                    <UserAvatar
                                        :name="row.c.peer.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="rp-focusable min-w-0 flex-1 text-left"
                                    @click="openPrivatePeer(row.c.peer)"
                                >
                                    <span class="block font-semibold text-[var(--rp-chat-sidebar-fg)]">{{
                                        row.c.peer.user_name
                                    }}</span>
                                    <span
                                        class="mt-0.5 block truncate text-xs text-[var(--rp-chat-sidebar-muted)]"
                                    >{{ (row.c.last_message && row.c.last_message.body) || '—' }}</span>
                                </button>
                            </div>
                            <p
                                v-else
                                class="rounded-md border border-dashed border-[var(--rp-chat-sidebar-border)] px-2 py-2 text-xs text-[var(--rp-chat-sidebar-muted)]"
                            >
                                Некоректний запис розмови
                            </p>
                        </li>
                    </ul>
                </div>

                <!-- Кімнати -->
                <div
                    v-show="sidebarTab === 'rooms'"
                    id="chat-panel-rooms"
                    role="tabpanel"
                    :aria-labelledby="panelTabLabelledby('rooms')"
                    tabindex="-1"
                    :aria-hidden="sidebarTab === 'rooms' ? 'false' : 'true'"
                >
                    <p v-if="loadingRooms" class="text-[var(--rp-chat-sidebar-muted)]">Завантаження…</p>
                    <ul v-else class="space-y-2">
                        <li v-for="r in rooms" :key="r.room_id">
                            <button
                                type="button"
                                class="rp-focusable rp-chat-side-room-btn w-full rounded-md border-2 px-3 py-2 text-left transition-colors"
                                :class="r.room_id === selectedRoomId ? 'is-active' : ''"
                                @click="selectRoom(r.room_id)"
                            >
                                <span class="block font-semibold text-[var(--rp-chat-sidebar-fg)]">{{
                                    r.room_name
                                }}</span>
                                <span
                                    v-if="r.topic"
                                    class="mt-0.5 block text-xs text-[var(--rp-chat-sidebar-muted)]"
                                >{{ r.topic }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Ігнор -->
                <div
                    v-show="sidebarTab === 'ignore'"
                    id="chat-panel-ignore"
                    role="tabpanel"
                    :aria-labelledby="panelTabLabelledby('ignore')"
                    tabindex="-1"
                    :aria-hidden="sidebarTab === 'ignore' ? 'false' : 'true'"
                >
                    <p
                        v-if="ignores.length === 0"
                        class="py-6 text-center text-[var(--rp-chat-sidebar-muted)]"
                    >
                        Список ігнор порожній
                    </p>
                    <ul v-else class="space-y-2">
                        <li
                            v-for="row in ignoresWithMenuPeer"
                            :key="row.user.id"
                            class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex min-w-0 flex-1 items-center gap-1">
                                    <div class="flex min-w-0 flex-1 items-center gap-2">
                                        <UserAvatar
                                            :name="row.user.user_name"
                                            variant="sidebar"
                                            decorative
                                        />
                                        <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                            row.user.user_name
                                        }}</span>
                                    </div>
                                    <SidebarHamburgerTrigger
                                        :expanded="sidebarBadgeMenuOpen('ign-' + row.user.id)"
                                        :aria-label="'Меню дій для ' + row.user.user_name"
                                        @activate="openPeerBadgeMenu($event, row.menuPeer, 'ign-' + row.user.id)"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="rp-focusable shrink-0 text-sm font-semibold text-[var(--rp-chat-sidebar-link)] hover:text-[var(--rp-chat-sidebar-link-hover)] hover:underline"
                                    @click="removeIgnore(row.user.id)"
                                >
                                    Зняти
                                </button>
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="sidebarBadgeMenuOpen('ign-' + row.user.id) && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="badgeMenu.target"
                                @pick="onSidebarBadgeMenuPick"
                                @close="closeSidebarBadgeMenu"
                            />
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="shrink-0 border-t border-white/10 px-4 py-4 md:hidden"
                style="background: var(--rp-burger-bg)"
            >
                <div
                    class="mx-auto max-w-[16rem] rounded-lg px-4 py-3 text-center shadow-md"
                    style="background: var(--rp-burger-footer-card-bg); color: var(--rp-burger-footer-card-fg)"
                >
                    <p class="font-serif text-lg font-semibold tracking-tight">redpanda</p>
                    <p class="mt-1 text-xs" style="color: var(--rp-burger-footer-muted)">Чат нового покоління</p>
                </div>
            </div>
        </aside>
        </div>

        <CommandsHelpModal :open="commandsHelpOpen" @close="commandsHelpOpen = false" />
        <UserInfoModal
            :open="userInfoModalOpen"
            :mode="userInfoModalMode"
            :viewer="user"
            :target="userInfoModalTarget"
            @close="closeUserInfoModal"
        />
        <SimpleStubModal
            :open="adminSettingsStubOpen"
            title="Налаштування чату"
            body="Екран адміністратора чату ще в розробці (TODO після узгодження з бекендом). Тут з’являться кімнати, модерація та глобальні параметри."
            @close="adminSettingsStubOpen = false"
        />
        <UserProfileModal
            :open="profileModalOpen"
            :user="user"
            @close="profileModalOpen = false"
            @updated="onProfileModalUpdated"
        />

        <PrivateChatPanel
            v-if="user && privatePeer"
            :peer="privatePeer"
            :messages="privateMessages"
            :loading="loadingPrivateMessages"
            :sending="sendingPrivate"
            :error="privateLoadError"
            :composer-text.sync="privateComposerText"
            :current-user-id="user.id"
            :current-user-name="user.user_name"
            :current-user-avatar-url="user.avatar_url || ''"
            @close="closePrivatePanel"
            @send="sendPrivateMessageFromPanel"
        />
    </div>
</template>

<script>
import CommandsHelpModal from '../components/CommandsHelpModal.vue';
import PrivateChatPanel from '../components/PrivateChatPanel.vue';
import SimpleStubModal from '../components/SimpleStubModal.vue';
import UserProfileModal from '../components/UserProfileModal.vue';
import UserBadgeInlineActionPanel from '../components/UserBadgeInlineActionPanel.vue';
import SidebarHamburgerTrigger from '../components/SidebarHamburgerTrigger.vue';
import UserInfoModal from '../components/UserInfoModal.vue';
import { createEcho } from '../lib/echo';

const THEME_KEY = 'redpanda-theme';

const SIDEBAR_TAB_ICONS = {
    users:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>',
    friends:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M21 5c-1.11-.35-2.33-.5-3.5-.5-1.95 0-4.05.4-5.5 1.5-1.45-1.1-3.55-1.5-5.5-1.5S2.45 4.9 1 6v14.65c0 .25.25.5.5.5.1 0 .15-.05.25-.05C3.1 20.45 5.05 20 6.5 20c1.95 0 4.05.4 5.5 1.5 1.35-.85 3.8-1.5 5.5-1.5 1.65 0 3.35.3 4.75 1.05.1.05.15.05.25.05.25 0 .5-.25.5-.5V6c-.6-.45-1.25-.75-2-1zm0 13.5c-1.1-.35-2.3-.5-3.5-.5-1.7 0-4.15.65-5.5 1.5V8c1.35-.85 3.8-1.5 5.5-1.5 1.2 0 2.4.15 3.5.5v11.5z"/></svg>',
    private:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/></svg>',
    rooms:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>',
    ignore:
        '<svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4zm4.5 1c.83 0 1.5-.67 1.5-1.5S17.33 10 16.5 10 15 10.67 15 11.5s.67 1.5 1.5 1.5zM19 3l-4 4 1.5 1.5L20.5 4 19 3z"/></svg>',
};

function peerTargetFromConversationPeerPayload(p) {
    if (!p) {
        return null;
    }

    return {
        id: p.id != null ? Number(p.id) : null,
        user_name: p.user_name != null ? String(p.user_name) : '',
        guest: Boolean(p.guest),
        chat_role: p.chat_role != null ? String(p.chat_role) : 'user',
    };
}

function peerTargetFromFriendUserPayload(u) {
    if (!u) {
        return null;
    }

    return {
        id: Number(u.id),
        user_name: u.user_name,
        guest: false,
        chat_role: 'user',
    };
}

/**
 * Початковий стан бічної панелі (друзі, приват, ігнор).
 * Поля мають збігатися з використанням у шаблоні — не прибирати точково при merge.
 * Прапор echoUserListenerReady додатково скидається в setupEcho при створенні нового Echo (HMR тощо).
 */
function createChatRoomSidebarState() {
    return {
        peerLookupName: '',
        conversations: [],
        friendsAccepted: [],
        friendsIncoming: [],
        friendsOutgoing: [],
        ignores: [],
        privatePeer: null,
        privateMessages: [],
        privateMessageIds: new Set(),
        privateComposerText: '',
        loadingPrivateMessages: false,
        sendingPrivate: false,
        privateLoadError: '',
        echoUserListenerReady: false,
        privateListLoadError: '',
        friendsIgnoresLoadError: '',
    };
}

function normalizePresencePeer(raw) {
    if (!raw || raw.id === undefined || raw.id === null) {
        return null;
    }

    return {
        id: Number(raw.id),
        user_name: raw.user_name != null ? String(raw.user_name) : '',
        guest: Boolean(raw.guest),
        avatar_url: raw.avatar_url != null ? String(raw.avatar_url) : '',
        chat_role: raw.chat_role != null ? String(raw.chat_role) : 'user',
        badge_color: raw.badge_color != null ? String(raw.badge_color) : '',
    };
}

function normalizeMessage(raw) {
    if (!raw || typeof raw.post_id === 'undefined') {
        return null;
    }

    const file = raw.file != null ? Number(raw.file) : 0;
    const image =
        raw.image && raw.image.url
            ? { id: Number(raw.image.id), url: raw.image.url }
            : null;

    return {
        post_id: raw.post_id,
        post_roomid: raw.post_roomid,
        user_id: raw.user_id,
        post_date: raw.post_date,
        post_time: raw.post_time,
        post_user: raw.post_user,
        post_message: raw.post_message,
        post_color: raw.post_color,
        type: raw.type,
        recipient_user_id:
            raw.recipient_user_id != null && raw.recipient_user_id !== ''
                ? Number(raw.recipient_user_id)
                : null,
        client_message_id: raw.client_message_id,
        avatar: raw.avatar ? String(raw.avatar) : '',
        file,
        image,
    };
}

export default {
    name: 'ChatRoom',
    components: {
        CommandsHelpModal,
        PrivateChatPanel,
        SimpleStubModal,
        UserProfileModal,
        UserBadgeInlineActionPanel,
        SidebarHamburgerTrigger,
        UserInfoModal,
    },
    data() {
        return {
            user: null,
            rooms: [],
            selectedRoomId: null,
            messages: [],
            messageIds: new Set(),
            composerText: '',
            loadingRooms: true,
            loadingMessages: false,
            sending: false,
            pendingImageId: null,
            pendingPreviewUrl: '',
            uploadingImage: false,
            imageUploadError: '',
            loadError: '',
            logoutError: '',
            echo: null,
            echoChannel: null,
            echoSubscribedRoomId: null,
            wsDegraded: false,
            pollTimer: null,
            themeUi: 'system',
            loggingOut: false,
            panelOpen: true,
            sidebarTab: 'rooms',
            friendsSubTab: 'active',
            isNarrowViewport: false,
            mqHandler: null,
            /** Елемент фокусу до відкриття off-canvas (повертаємо при закритті). */
            panelFocusReturnEl: null,
            /** Інші учасники поточної кімнати (Echo presence), без поточного користувача. */
            roomPresencePeers: [],
            ...createChatRoomSidebarState(),
            badgeMenu: null,
            commandsHelpOpen: false,
            userInfoModalOpen: false,
            userInfoModalMode: 'self',
            userInfoModalTarget: null,
            adminSettingsStubOpen: false,
            profileModalOpen: false,
        };
    },
    computed: {
        themeLabel() {
            if (this.themeUi === 'light') {
                return 'Тема: світла';
            }
            if (this.themeUi === 'dark') {
                return 'Тема: темна';
            }

            return 'Тема: як у системі';
        },
        currentRoom() {
            return this.rooms.find((r) => r.room_id === this.selectedRoomId) || null;
        },
        chatBreadcrumb() {
            const u = this.user && this.user.user_name;
            const r = this.currentRoom && this.currentRoom.room_name;
            if (u && r) {
                return `${u} › ${r}`;
            }
            if (r) {
                return r;
            }
            if (u) {
                return u;
            }

            return '';
        },
        chatTopicLine() {
            return this.currentRoom && this.currentRoom.topic ? this.currentRoom.topic : '';
        },
        sidebarTabs() {
            return [
                { id: 'users', title: 'Люди', icon: SIDEBAR_TAB_ICONS.users },
                { id: 'friends', title: 'Друзі', icon: SIDEBAR_TAB_ICONS.friends },
                { id: 'private', title: 'Приват', icon: SIDEBAR_TAB_ICONS.private },
                { id: 'rooms', title: 'Кімнати', icon: SIDEBAR_TAB_ICONS.rooms },
                { id: 'ignore', title: 'Ігнор', icon: SIDEBAR_TAB_ICONS.ignore },
            ];
        },
        privateConversationRows() {
            const list = this.conversations || [];

            return list.map((c, idx) => {
                const key =
                    c && c.peer && c.peer.id != null ? `peer-${c.peer.id}` : `conv-${idx}`;
                const menuPeer =
                    c && c.peer && c.peer.id != null
                        ? peerTargetFromConversationPeerPayload(c.peer)
                        : null;

                return { c, idx, key, menuPeer };
            });
        },
        friendsAcceptedWithMenuPeer() {
            return (this.friendsAccepted || []).map((f) => ({
                ...f,
                menuPeer: peerTargetFromFriendUserPayload(f.user),
            }));
        },
        friendsIncomingWithMenuPeer() {
            return (this.friendsIncoming || []).map((r) => ({
                ...r,
                menuPeer: peerTargetFromFriendUserPayload(r.user),
            }));
        },
        friendsOutgoingWithMenuPeer() {
            return (this.friendsOutgoing || []).map((r) => ({
                ...r,
                menuPeer: peerTargetFromFriendUserPayload(r.user),
            }));
        },
        ignoresWithMenuPeer() {
            return (this.ignores || []).map((row) => ({
                ...row,
                menuPeer: peerTargetFromFriendUserPayload(row.user),
            }));
        },
    },
    watch: {
        panelOpen() {
            this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
        },
        isNarrowViewport() {
            this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
        },
        sidebarTab(to) {
            this.badgeMenu = null;
            if (to === 'private') {
                this.loadConversations();
            }
            if (to === 'friends' || to === 'ignore') {
                this.loadFriendsAndIgnores();
            }
        },
        badgeMenu(to) {
            document.removeEventListener('mousedown', this.onSidebarBadgeMenuDocMouseDown, true);
            document.removeEventListener('keydown', this.onSidebarBadgeMenuDocKeydown, true);
            if (to) {
                document.addEventListener('mousedown', this.onSidebarBadgeMenuDocMouseDown, true);
                document.addEventListener('keydown', this.onSidebarBadgeMenuDocKeydown, true);
            }
        },
        /** Зміна акаунту / вихід без повного reload: перепідписати приватний канал user.{id}. */
        user(to, from) {
            if (!this.echo) {
                return;
            }
            const prevId = from && from.id;
            const nextId = to && to.id;
            if (prevId != null && nextId != null && Number(prevId) === Number(nextId)) {
                return;
            }
            if (prevId != null) {
                try {
                    this.echo.leave(`user.${prevId}`);
                } catch {
                    /* */
                }
            }
            this.echoUserListenerReady = false;
            if (nextId != null) {
                this.$nextTick(() => this.ensureUserPrivateListener());
            }
        },
    },
    created() {
        this.themeUi = localStorage.getItem(THEME_KEY) || 'system';
    },
    async mounted() {
        document.documentElement.setAttribute('data-theme', this.themeUi);
        this.initViewportListener();
        await this.bootstrap();
        window.addEventListener('keydown', this.onGlobalKeydown);
    },
    beforeDestroy() {
        document.removeEventListener('mousedown', this.onSidebarBadgeMenuDocMouseDown, true);
        document.removeEventListener('keydown', this.onSidebarBadgeMenuDocKeydown, true);
        window.removeEventListener('keydown', this.onGlobalKeydown);
        this.teardownMediaQuery();
        document.body.style.overflow = '';
        this.teardownEcho(true);
        this.stopPoll();
    },
    methods: {
        nickColorStyle(m) {
            if (!m || !m.post_user) {
                return {};
            }
            if (m.post_color === 'guest') {
                return { color: 'var(--rp-text-muted)' };
            }
            if (m.post_color === 'vip') {
                return { color: '#c2410c' };
            }
            if (m.post_color === 'mod') {
                return { color: '#15803d' };
            }
            if (m.post_color === 'admin') {
                return { color: 'var(--rp-chat-role-admin)' };
            }
            /* Темні відтінки ≥ ~4.5:1 на білому для жирного ~15px (WCAG AA) */
            const palette = [
                '#9a3412',
                '#c2410c',
                '#1e3a8a',
                '#115e59',
                '#5b21b6',
                '#991b1b',
                '#155e75',
            ];
            let h = 0;
            const n = m.post_user;
            for (let i = 0; i < n.length; i++) {
                h = n.charCodeAt(i) + ((h << 5) - h);
            }

            return { color: palette[Math.abs(h) % palette.length] };
        },
        initViewportListener() {
            if (typeof window === 'undefined' || !window.matchMedia) {
                return;
            }
            const mq = window.matchMedia('(max-width: 767px)');
            this.isNarrowViewport = mq.matches;
            this.panelOpen = !mq.matches;
            this.mqHandler = () => {
                this.isNarrowViewport = mq.matches;
                if (!mq.matches) {
                    this.panelOpen = true;
                }
                this.$nextTick(() => {
                    this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
                });
            };
            mq.addEventListener('change', this.mqHandler);
        },
        teardownMediaQuery() {
            if (typeof window === 'undefined' || !window.matchMedia || !this.mqHandler) {
                return;
            }
            const mq = window.matchMedia('(max-width: 767px)');
            mq.removeEventListener('change', this.mqHandler);
            this.mqHandler = null;
        },
        syncBodyScrollLock(lock) {
            document.body.style.overflow = lock ? 'hidden' : '';
        },
        onGlobalKeydown(e) {
            if (e.key !== 'Escape') {
                return;
            }
            if (this.privatePeer) {
                this.closePrivatePanel();

                return;
            }
            if (this.panelOpen && this.isNarrowViewport) {
                this.closePanel();
            }
        },
        focusPanelCloseButton() {
            this.$nextTick(() => {
                const btn =
                    (this.isNarrowViewport && this.$refs.panelCloseBtnMobile) ||
                    this.$refs.panelCloseBtnDesktop ||
                    this.$refs.panelCloseBtnMobile;
                if (btn && typeof btn.focus === 'function') {
                    btn.focus();
                }
            });
        },
        panelTabLabelledby(panelKey) {
            return (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + panelKey;
        },
        /** Відкрити панель і перевести фокус на кнопку закриття (off-canvas). */
        beginOpeningPanel() {
            if (!this.panelOpen) {
                this.panelFocusReturnEl = document.activeElement;
            }
            this.panelOpen = true;
            if (this.isNarrowViewport) {
                this.focusPanelCloseButton();
            }
        },
        closePanel() {
            if (!this.panelOpen) {
                return;
            }
            const returnEl = this.panelFocusReturnEl;
            this.panelFocusReturnEl = null;
            this.panelOpen = false;
            this.$nextTick(() => {
                if (returnEl && typeof returnEl.focus === 'function') {
                    try {
                        returnEl.focus();
                    } catch {
                        /* */
                    }
                }
            });
        },
        togglePanel() {
            if (this.panelOpen) {
                this.closePanel();
            } else {
                this.beginOpeningPanel();
            }
        },
        selectSidebarTab(id) {
            this.sidebarTab = id;
            this.$nextTick(() => {
                const domId = (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + id;
                const el = document.getElementById(domId);
                if (el && typeof el.focus === 'function') {
                    el.focus();
                }
            });
        },
        onSidebarTabKeydown(e) {
            const ids = this.sidebarTabs.map((t) => t.id);
            const i = ids.indexOf(this.sidebarTab);
            if (i < 0) {
                return;
            }
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                const delta = e.key === 'ArrowRight' ? 1 : -1;
                const next = ids[(i + delta + ids.length) % ids.length];
                this.selectSidebarTab(next);
            }
            if (e.key === 'Home') {
                e.preventDefault();
                this.selectSidebarTab(ids[0]);
            }
            if (e.key === 'End') {
                e.preventDefault();
                this.selectSidebarTab(ids[ids.length - 1]);
            }
        },
        cycleTheme() {
            const order = ['system', 'light', 'dark'];
            const i = Math.max(0, order.indexOf(this.themeUi));
            const next = order[(i + 1) % order.length];
            this.themeUi = next;
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem(THEME_KEY, next);
        },
        async logout() {
            this.loggingOut = true;
            this.logoutError = '';
            try {
                await this.ensureSanctum();
                await window.axios.post('/api/v1/auth/logout');
                this.teardownEcho(true);
                this.stopPoll();
                this.user = null;
                await this.$router.replace({ path: '/' });
            } catch {
                this.logoutError = 'Не вдалося вийти. Спробуйте ще раз.';
            } finally {
                this.loggingOut = false;
            }
        },
        async ensureSanctum() {
            await window.axios.get('/sanctum/csrf-cookie');
        },
        async fetchUser() {
            try {
                const { data } = await window.axios.get('/api/v1/auth/user');

                return data.data;
            } catch {
                return null;
            }
        },
        onProfileModalUpdated(nextUser) {
            if (nextUser) {
                this.user = nextUser;
            }
        },
        async bootstrap() {
            this.user = await this.fetchUser();
            if (!this.user) {
                this.$router.replace({ path: '/' });

                return;
            }

            await this.loadRooms();
            const qRoom = this.$route.query.room;
            const fromQuery = qRoom ? Number(qRoom) : null;
            if (fromQuery && this.rooms.some((r) => r.room_id === fromQuery)) {
                this.selectedRoomId = fromQuery;
            } else if (this.rooms.length > 0) {
                this.selectedRoomId = this.rooms[0].room_id;
            }

            if (this.selectedRoomId) {
                this.$router.replace({ path: '/chat', query: { room: String(this.selectedRoomId) } }).catch(() => {});
                await this.applyRoomSelection();
            }

            await Promise.all([this.loadConversations(), this.loadFriendsAndIgnores()]);
        },
        async loadRooms() {
            this.loadingRooms = true;
            this.loadError = '';
            try {
                const { data } = await window.axios.get('/api/v1/rooms');
                this.rooms = data.data || [];
            } catch {
                this.loadError = 'Не вдалося завантажити кімнати.';
                this.rooms = [];
            } finally {
                this.loadingRooms = false;
            }
        },
        clearMessages() {
            this.messages = [];
            this.messageIds = new Set();
        },
        mergeMessage(raw) {
            const m = normalizeMessage(raw);
            if (!m || this.messageIds.has(m.post_id)) {
                return;
            }
            const rid = this.selectedRoomId;
            if (
                rid != null
                && m.post_roomid != null
                && Number(m.post_roomid) !== Number(rid)
            ) {
                return;
            }
            this.messageIds.add(m.post_id);
            this.messages.push(m);
            this.messages.sort((a, b) => a.post_id - b.post_id);
            this.$nextTick(() => this.scrollToBottom());
        },
        scrollToBottom() {
            const el = this.$refs.messageList;
            if (el) {
                el.scrollTop = el.scrollHeight;
            }
        },
        async loadMessages() {
            if (!this.selectedRoomId) {
                return;
            }
            this.loadingMessages = true;
            try {
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomId}/messages`,
                    { params: { limit: 80 } },
                );
                this.clearMessages();
                (data.data || []).forEach((row) => this.mergeMessage(row));
            } catch {
                this.loadError = 'Не вдалося завантажити повідомлення.';
            } finally {
                this.loadingMessages = false;
                this.$nextTick(() => this.scrollToBottom());
            }
        },
        async pollNewMessages() {
            if (!this.selectedRoomId || !this.wsDegraded) {
                return;
            }
            try {
                const { data } = await window.axios.get(
                    `/api/v1/rooms/${this.selectedRoomId}/messages`,
                    { params: { limit: 80 } },
                );
                (data.data || []).forEach((row) => this.mergeMessage(row));
            } catch {
                /* ignore */
            }
        },
        startPollIfDegraded() {
            this.stopPoll();
            if (!this.wsDegraded) {
                return;
            }
            this.pollTimer = window.setInterval(() => this.pollNewMessages(), 10000);
        },
        stopPoll() {
            if (this.pollTimer !== null) {
                clearInterval(this.pollTimer);
                this.pollTimer = null;
            }
        },
        syncPresenceHere(users) {
            const myId = this.user && this.user.id != null ? Number(this.user.id) : null;
            const list = (users || [])
                .map((u) => normalizePresencePeer(u))
                .filter((p) => p && myId !== null && p.id !== myId);
            list.sort((a, b) => a.user_name.localeCompare(b.user_name, 'uk'));
            this.roomPresencePeers = list;
        },
        addPresencePeer(raw) {
            const p = normalizePresencePeer(raw);
            const myId = this.user && this.user.id != null ? Number(this.user.id) : null;
            if (!p || myId === null || p.id === myId) {
                return;
            }
            if (this.roomPresencePeers.some((x) => x.id === p.id)) {
                return;
            }
            const next = [...this.roomPresencePeers, p];
            next.sort((a, b) => a.user_name.localeCompare(b.user_name, 'uk'));
            this.roomPresencePeers = next;
        },
        removePresencePeer(raw) {
            const id = raw && raw.id != null ? Number(raw.id) : null;
            if (id == null) {
                return;
            }
            this.roomPresencePeers = this.roomPresencePeers.filter((x) => x.id !== id);
        },
        teardownEcho(fullDisconnect = false) {
            if (this.echo && this.echoSubscribedRoomId !== null) {
                try {
                    this.echo.leave(`room.${this.echoSubscribedRoomId}`);
                } catch {
                    /* */
                }
            }
            this.echoSubscribedRoomId = null;
            this.echoChannel = null;
            this.roomPresencePeers = [];

            if (fullDisconnect && this.echo) {
                if (this.user) {
                    try {
                        this.echo.leave(`user.${this.user.id}`);
                    } catch {
                        /* */
                    }
                }
                this.echoUserListenerReady = false;
                try {
                    this.echo.disconnect();
                } catch {
                    /* */
                }
                this.echo = null;
            }
        },
        setupEcho() {
            this.teardownEcho(false);

            let echo = this.echo;
            if (!echo) {
                echo = createEcho();
                if (!echo) {
                    this.wsDegraded = true;
                    this.startPollIfDegraded();

                    return;
                }
                this.echo = echo;
                this.echoUserListenerReady = false;
            }

            this.wsDegraded = false;

            const roomId = this.selectedRoomId;
            if (roomId == null) {
                return;
            }

            this.echoSubscribedRoomId = roomId;
            this.roomPresencePeers = [];

            const channel = echo.join(`room.${roomId}`);

            channel.here((users) => {
                this.syncPresenceHere(users);
            });
            channel.joining((u) => {
                this.addPresencePeer(u);
            });
            channel.leaving((u) => {
                this.removePresencePeer(u);
            });

            channel.subscribed(() => {
                this.wsDegraded = false;
                this.stopPoll();
            });

            channel.error(() => {
                this.wsDegraded = true;
                this.roomPresencePeers = [];
                this.startPollIfDegraded();
            });

            channel.listen('.MessagePosted', (payload) => {
                this.mergeMessage(payload);
            });

            this.echoChannel = channel;
            this.ensureUserPrivateListener();
        },
        ensureUserPrivateListener() {
            if (!this.echo || !this.user || this.echoUserListenerReady) {
                return;
            }
            this.echoUserListenerReady = true;
            const ch = this.echo.private(`user.${this.user.id}`);
            ch.listen('.PrivateMessagePosted', (payload) => {
                this.onPrivateWsPayload(payload);
            });
            ch.listen('.RoomInlinePrivatePosted', (payload) => {
                this.mergeMessage(payload);
            });
        },
        onPrivateWsPayload(payload) {
            if (!payload || typeof payload.id === 'undefined' || !this.user) {
                return;
            }
            if (Number(payload.recipient_id) !== Number(this.user.id)) {
                return;
            }
            if (
                this.privatePeer
                && Number(payload.sender_id) === Number(this.privatePeer.id)
            ) {
                this.mergePrivateMessage(payload);
                this.privateMessages.sort((a, b) => a.id - b.id);
            }
            this.loadConversations();
        },
        async loadConversations() {
            if (!this.user) {
                return;
            }
            try {
                this.privateListLoadError = '';
                const { data } = await window.axios.get('/api/v1/private/conversations');
                const list = data && data.data;
                this.conversations = Array.isArray(list) ? list : [];
            } catch {
                this.conversations = [];
                this.privateListLoadError = 'Не вдалося завантажити список розмов.';
            }
        },
        async loadFriendsAndIgnores() {
            if (!this.user) {
                return;
            }
            try {
                this.friendsIgnoresLoadError = '';
                const [acc, inc, out, ign] = await Promise.all([
                    window.axios.get('/api/v1/friends'),
                    window.axios.get('/api/v1/friends/requests/incoming'),
                    window.axios.get('/api/v1/friends/requests/outgoing'),
                    window.axios.get('/api/v1/ignores'),
                ]);
                const pickList = (res) => {
                    const d = res && res.data && res.data.data;

                    return Array.isArray(d) ? d : [];
                };
                this.friendsAccepted = pickList(acc);
                this.friendsIncoming = pickList(inc);
                this.friendsOutgoing = pickList(out);
                this.ignores = pickList(ign);
            } catch {
                this.friendsAccepted = [];
                this.friendsIncoming = [];
                this.friendsOutgoing = [];
                this.ignores = [];
                this.friendsIgnoresLoadError = 'Не вдалося завантажити друзів або список ігнору.';
            }
        },
        openPrivatePeer(peer) {
            if (!peer || !peer.id) {
                return;
            }
            this.privatePeer = { id: peer.id, user_name: peer.user_name };
            this.privateLoadError = '';
            this.privateMessages = [];
            this.privateMessageIds = new Set();
            this.sidebarTab = 'private';
            this.loadPrivateMessages();
        },
        closePrivatePanel() {
            this.privatePeer = null;
            this.privateMessages = [];
            this.privateMessageIds = new Set();
            this.privateComposerText = '';
            this.privateLoadError = '';
        },
        async lookupAndOpenPrivate() {
            if (!this.peerLookupName) {
                return;
            }
            await this.openPrivateByUserName(this.peerLookupName);
            this.peerLookupName = '';
        },
        async openPrivateByUserName(name) {
            if (!name || !this.user) {
                return;
            }
            try {
                const { data } = await window.axios.get('/api/v1/users/lookup', {
                    params: { name },
                });
                this.openPrivatePeer(data.data);
                this.loadError = '';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Користувача не знайдено.';
            }
        },
        sidebarBadgeMenuOpen(rowKey) {
            return Boolean(this.badgeMenu && this.badgeMenu.rowKey === rowKey);
        },
        sidebarPresenceMenuRowKey(p) {
            if (!p) {
                return 'presence-null';
            }
            if (p.id != null) {
                return `presence-${p.id}`;
            }

            const slug = String(p.user_name || 'guest')
                .replace(/\s+/g, '_')
                .replace(/[^a-zA-Z0-9_-]/g, '')
                .slice(0, 48);

            return `presence-g-${slug || 'x'}`;
        },
        closeSidebarBadgeMenu() {
            const rf = this.badgeMenu && this.badgeMenu.returnFocusEl;
            this.badgeMenu = null;
            this.$nextTick(() => {
                if (rf && typeof rf.focus === 'function') {
                    try {
                        rf.focus();
                    } catch {
                        /* */
                    }
                }
            });
        },
        onSidebarBadgeMenuDocMouseDown(e) {
            if (!this.badgeMenu) {
                return;
            }
            if (e.target.closest && e.target.closest('[data-rp-user-badge-menu-trigger]')) {
                return;
            }
            if (e.target.closest && e.target.closest('[data-rp-user-badge-inline-menu]')) {
                return;
            }
            this.closeSidebarBadgeMenu();
        },
        onSidebarBadgeMenuDocKeydown(e) {
            if (e.key !== 'Escape' || !this.badgeMenu) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();
            this.closeSidebarBadgeMenu();
        },
        onSidebarBadgeMenuPick(id) {
            this.onBadgeMenuPick(id);
            this.badgeMenu = null;
        },
        openSelfBadgeMenu(evt, rowKey) {
            if (!this.user || !evt || !evt.currentTarget || !rowKey) {
                return;
            }
            const el = evt.currentTarget;
            if (this.badgeMenu && this.badgeMenu.rowKey === rowKey && this.badgeMenu.returnFocusEl === el) {
                this.badgeMenu = null;

                return;
            }
            this.badgeMenu = {
                mode: 'self',
                target: null,
                rowKey,
                returnFocusEl: el,
            };
        },
        openPeerBadgeMenu(evt, target, rowKey) {
            if (!this.user || !target || !evt || !evt.currentTarget || !rowKey) {
                return;
            }
            const el = evt.currentTarget;
            if (this.badgeMenu && this.badgeMenu.rowKey === rowKey && this.badgeMenu.returnFocusEl === el) {
                this.badgeMenu = null;

                return;
            }
            this.badgeMenu = {
                mode: 'other',
                target: { ...target },
                rowKey,
                returnFocusEl: el,
            };
        },
        focusComposerEnd() {
            const el = this.$refs.chatComposer;
            if (!el || typeof el.focus !== 'function') {
                return;
            }
            el.focus();
            const len = this.composerText.length;
            try {
                el.setSelectionRange(len, len);
            } catch {
                /* */
            }
        },
        appendToComposer(insertion) {
            if (insertion == null || insertion === '') {
                return;
            }
            const t = this.composerText;
            if (t.length === 0) {
                this.composerText = insertion;

                return;
            }
            const needsSpace = !/\s$/.test(t);
            this.composerText = needsSpace ? `${t} ${insertion}` : t + insertion;
        },
        insertFeedReplyPrefix(userName) {
            if (!this.user || userName == null || userName === '') {
                return;
            }
            const nick = String(userName);
            const prefix = `${nick} > `;
            this.appendToComposer(prefix);
            this.$nextTick(() => this.focusComposerEnd());
        },
        insertFeedInlinePrivatePrefix(userName) {
            if (!this.user || userName == null || userName === '') {
                return;
            }
            const nick = String(userName);
            if (nick === this.user.user_name) {
                return;
            }
            const prefix = `/msg ${nick} `;
            this.appendToComposer(prefix);
            this.$nextTick(() => this.focusComposerEnd());
        },
        closeUserInfoModal() {
            this.userInfoModalOpen = false;
            this.userInfoModalTarget = null;
        },
        onBadgeMenuPick(id) {
            const bm = this.badgeMenu;
            if (!bm || !this.user) {
                return;
            }
            if (id === 'info') {
                this.userInfoModalMode = bm.mode;
                this.userInfoModalTarget = bm.mode === 'self' ? null : bm.target;
                this.userInfoModalOpen = true;

                return;
            }
            if (id === 'commands') {
                this.commandsHelpOpen = true;

                return;
            }
            if (id === 'settings') {
                this.adminSettingsStubOpen = true;

                return;
            }
            if (id === 'profile') {
                this.profileModalOpen = true;

                return;
            }
            if (id === 'private') {
                this.openPrivateFromMenuTarget(bm.target);

                return;
            }
            if (id === 'ignore') {
                this.addIgnoreFromMenuTarget(bm.target);

                return;
            }
            if (id === 'friend') {
                this.sendFriendFromMenuTarget(bm.target);

                return;
            }
            if (id === 'mute') {
                this.modMuteFromMenuTarget(bm.target);

                return;
            }
            if (id === 'kick') {
                this.modKickFromMenuTarget(bm.target);

                return;
            }
        },
        openPrivateFromMenuTarget(t) {
            if (!t) {
                return;
            }
            if (t.id != null) {
                this.openPrivatePeer({ id: t.id, user_name: t.user_name });

                return;
            }
            if (t.user_name) {
                this.openPrivateByUserName(t.user_name);
            }
        },
        async addIgnoreFromMenuTarget(t) {
            if (!t || t.id == null) {
                this.loadError =
                    'Потрібен обліковий запис із id (наприклад зі списку онлайн). Спробуйте через нік у полі «Приват за ніком» та вкладку профілю.';

                return;
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/ignores/${t.id}`);
                this.loadError = '';
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося додати до ігнору.';
            }
        },
        async sendFriendFromMenuTarget(t) {
            if (!t || t.id == null) {
                this.loadError = 'Немає id користувача для запиту в друзі.';

                return;
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/friends/${t.id}`);
                this.loadError = '';
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося надіслати запит у друзі.';
            }
        },
        async modMuteFromMenuTarget(t) {
            if (!t || t.id == null) {
                return;
            }
            const raw = window.prompt('Кляп: хвилини (0 — зняти)', '60');
            if (raw === null) {
                return;
            }
            const trimmed = raw.trim();
            let minutes;
            if (trimmed === '') {
                minutes = 0;
            } else {
                minutes = parseInt(trimmed, 10);
                if (Number.isNaN(minutes)) {
                    minutes = 60;
                }
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/mod/users/${t.id}/mute`, { minutes });
                this.loadError = '';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося застосувати кляп.';
            }
        },
        async modKickFromMenuTarget(t) {
            if (!t || t.id == null) {
                return;
            }
            const raw = window.prompt('Вигнання: хвилини (0 — зняти)', '60');
            if (raw === null) {
                return;
            }
            const trimmed = raw.trim();
            let minutes;
            if (trimmed === '') {
                minutes = 0;
            } else {
                minutes = parseInt(trimmed, 10);
                if (Number.isNaN(minutes)) {
                    minutes = 60;
                }
            }
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/mod/users/${t.id}/kick`, { minutes });
                this.loadError = '';
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося застосувати вигнання.';
            }
        },
        async loadPrivateMessages() {
            if (!this.privatePeer) {
                return;
            }
            this.loadingPrivateMessages = true;
            this.privateLoadError = '';
            try {
                const { data } = await window.axios.get(
                    `/api/v1/private/peers/${this.privatePeer.id}/messages`,
                    { params: { limit: 80 } },
                );
                this.privateMessageIds = new Set();
                this.privateMessages = [];
                (data.data || []).forEach((row) => this.mergePrivateMessage(row));
                this.privateMessages.sort((a, b) => a.id - b.id);
            } catch (e) {
                this.privateLoadError = e.response?.data?.message || 'Не вдалося завантажити приват.';
            } finally {
                this.loadingPrivateMessages = false;
            }
        },
        mergePrivateMessage(raw) {
            if (!raw || typeof raw.id === 'undefined' || this.privateMessageIds.has(raw.id)) {
                return;
            }
            if (!this.privatePeer || !this.user) {
                return;
            }
            const uid = Number(this.user.id);
            const pid = Number(this.privatePeer.id);
            const sid = Number(raw.sender_id);
            const rid = Number(raw.recipient_id);
            const inThread = (sid === uid && rid === pid) || (sid === pid && rid === uid);
            if (!inThread) {
                return;
            }
            this.privateMessageIds.add(raw.id);
            this.privateMessages.push({
                id: raw.id,
                sender_id: raw.sender_id,
                recipient_id: raw.recipient_id,
                body: raw.body,
                sent_at: raw.sent_at,
                sent_time: raw.sent_time,
                client_message_id: raw.client_message_id,
            });
        },
        async sendPrivateMessageFromPanel(body) {
            if (!this.privatePeer || this.sendingPrivate) {
                return;
            }
            const text = typeof body === 'string' ? body.trim() : '';
            if (!text) {
                return;
            }
            this.sendingPrivate = true;
            await this.ensureSanctum();
            const clientMessageId = crypto.randomUUID();
            try {
                const { data, status } = await window.axios.post(
                    `/api/v1/private/peers/${this.privatePeer.id}/messages`,
                    {
                        message: text,
                        client_message_id: clientMessageId,
                    },
                );
                if (data.data) {
                    this.mergePrivateMessage(data.data);
                    this.privateMessages.sort((a, b) => a.id - b.id);
                }
                if (status === 201 || status === 200) {
                    this.privateComposerText = '';
                }
                await this.loadConversations();
            } catch (e) {
                this.privateLoadError = e.response?.data?.message || 'Не вдалося надіслати.';
            } finally {
                this.sendingPrivate = false;
            }
        },
        async acceptFriend(userId) {
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/friends/${userId}/accept`);
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося прийняти запит.';
            }
        },
        async rejectFriend(userId) {
            await this.ensureSanctum();
            try {
                await window.axios.post(`/api/v1/friends/${userId}/reject`);
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося відхилити запит.';
            }
        },
        async removeIgnore(userId) {
            await this.ensureSanctum();
            try {
                await window.axios.delete(`/api/v1/ignores/${userId}`);
                await this.loadFriendsAndIgnores();
            } catch (e) {
                this.loadError = e.response?.data?.message || 'Не вдалося зняти ігнор.';
            }
        },
        async applyRoomSelection() {
            this.teardownEcho(false);
            this.clearMessages();
            this.loadError = '';
            await this.loadMessages();
            this.setupEcho();
            this.startPollIfDegraded();
        },
        async selectRoom(roomId) {
            if (!roomId || roomId === this.selectedRoomId) {
                return;
            }
            this.selectedRoomId = roomId;
            this.$router.replace({ path: '/chat', query: { room: String(roomId) } }).catch(() => {});
            await this.applyRoomSelection();
            if (this.isNarrowViewport && this.panelOpen) {
                this.panelFocusReturnEl = this.$refs.mobilePanelToggle || this.panelFocusReturnEl;
                this.closePanel();
            }
        },
        clearPendingChatImage() {
            this.pendingImageId = null;
            this.pendingPreviewUrl = '';
            this.imageUploadError = '';
            if (this.$refs.imageInput) {
                this.$refs.imageInput.value = '';
            }
        },
        onChatComposerKeydown(e) {
            if (e.key !== 'Enter') {
                return;
            }
            if (e.isComposing || e.keyCode === 229) {
                return;
            }
            if (e.shiftKey) {
                return;
            }
            e.preventDefault();
            this.sendMessage();
        },
        async onChatImageSelected(e) {
            const input = e.target;
            const file = input.files && input.files[0];
            if (!file || !this.selectedRoomId) {
                return;
            }
            this.imageUploadError = '';
            this.uploadingImage = true;
            await this.ensureSanctum();
            try {
                const form = new FormData();
                form.append('image', file);
                const { data } = await window.axios.post('/api/v1/images', form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                this.pendingImageId = data.data.id;
                this.pendingPreviewUrl = data.data.url;
            } catch (err) {
                const msg = err.response?.data?.message || 'Не вдалося завантажити зображення.';
                this.imageUploadError = msg;
                this.clearPendingChatImage();
            } finally {
                this.uploadingImage = false;
                input.value = '';
            }
        },
        async sendMessage() {
            const text = this.composerText.trim();
            if ((!text && !this.pendingImageId) || !this.selectedRoomId || this.sending) {
                return;
            }
            this.sending = true;
            await this.ensureSanctum();
            const clientMessageId = crypto.randomUUID();
            try {
                const body = {
                    message: text,
                    client_message_id: clientMessageId,
                };
                if (this.pendingImageId) {
                    body.image_id = this.pendingImageId;
                }
                const { data, status } = await window.axios.post(
                    `/api/v1/rooms/${this.selectedRoomId}/messages`,
                    body,
                );
                if (data.data) {
                    this.mergeMessage(data.data);
                }
                if (status === 201 || status === 200) {
                    this.composerText = '';
                    this.clearPendingChatImage();
                }
            } catch (e) {
                const msg = e.response?.data?.message || 'Не вдалося надіслати.';
                this.loadError = msg;
            } finally {
                this.sending = false;
            }
        },
    },
};
</script>
