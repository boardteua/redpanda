<template>
    <div
        class="flex min-h-screen flex-col bg-[var(--rp-bg)] md:h-[100dvh] md:max-h-screen md:flex-row md:overflow-hidden md:p-0"
    >
        <!-- Затемнення (лише мобільний off-canvas) -->
        <button
            v-if="panelOpen && isNarrowViewport"
            type="button"
            class="rp-focusable fixed inset-0 z-40 bg-black/40 md:hidden"
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
                        title="Люди"
                        @click="togglePanel"
                    >
                        <span class="rp-sr-only">Відкрити або сховати панель чату</span>
                        <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
                            />
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
                                :class="
                                    msgIdx % 2 === 0
                                        ? 'bg-[var(--rp-chat-row-even)]'
                                        : 'bg-[var(--rp-chat-row-odd)]'
                                "
                            >
                                <UserAvatar
                                    :src="m.avatar"
                                    :name="m.post_user"
                                    variant="feed"
                                    decorative
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                                        <p class="min-w-0 flex-1 leading-snug text-[var(--rp-text)]">
                                            <button
                                                v-if="user && m.post_user !== user.user_name"
                                                type="button"
                                                class="rp-focusable mr-1.5 inline font-semibold hover:underline"
                                                :style="nickColorStyle(m)"
                                                @click="openPrivateByUserName(m.post_user)"
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
                                title="Закреслення (згодом)"
                                aria-disabled="true"
                            >
                                <span class="text-sm line-through" aria-hidden="true">S</span>
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
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Смайли (згодом)"
                                aria-disabled="true"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"
                                    />
                                </svg>
                            </button>
                            <button
                                type="button"
                                class="rp-focusable rp-chat-toolbar-btn"
                                disabled
                                title="Файл (згодом)"
                                aria-disabled="true"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path
                                        d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <label class="rp-sr-only" for="chat-composer">Повідомлення</label>
                        <div class="flex items-end gap-2 px-2 pb-2 pt-1 sm:px-3 sm:pb-2.5">
                            <div class="rp-chat-composer-slot">
                                <button
                                    type="button"
                                    class="rp-focusable rp-chat-composer-inner-btn left-1.5"
                                    disabled
                                    title="Смайли всередині поля (згодом)"
                                    aria-label="Смайли"
                                >
                                    <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                        <path
                                            d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"
                                        />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    class="rp-focusable rp-chat-composer-inner-btn right-1.5"
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
                                <textarea
                                    id="chat-composer"
                                    v-model="composerText"
                                    class="rp-focusable rp-chat-composer-input w-full resize-y"
                                    maxlength="4000"
                                    rows="3"
                                    :disabled="sending || uploadingImage || !selectedRoomId"
                                    placeholder="Повідомлення (Жміть кнопку ⇧, щоб поправити останні повідомлення — згодом)"
                                />
                            </div>
                            <button
                                type="submit"
                                class="rp-focusable rp-chat-send-fab"
                                :disabled="
                                    sending
                                    || uploadingImage
                                    || !selectedRoomId
                                    || (!composerText.trim() && !pendingImageId)
                                "
                                title="Надіслати"
                                aria-label="Надіслати повідомлення"
                            >
                                <svg class="h-5 w-5" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                                </svg>
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
            class="rp-chat-sidebar flex w-[320px] max-w-[100vw] flex-shrink-0 flex-col border-l border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-bg)] text-[var(--rp-chat-sidebar-fg)] max-md:fixed max-md:inset-y-0 max-md:right-0 max-md:z-50 max-md:shadow-lg max-md:transition-transform max-md:duration-200 max-md:ease-out md:relative md:z-auto md:min-h-0 md:self-stretch md:shadow-none md:transition-none"
            :class="[
                isNarrowViewport && (panelOpen ? 'max-md:translate-x-0' : 'max-md:translate-x-full'),
                !isNarrowViewport && !panelOpen ? 'md:hidden' : '',
            ]"
            aria-label="Панель чату"
        >
            <div
                class="flex items-center justify-between gap-2 border-b border-[var(--rp-chat-sidebar-border)] px-3 py-2"
            >
                <h2 class="text-sm font-semibold text-[var(--rp-chat-sidebar-fg)]">Панель</h2>
                <button
                    ref="panelCloseBtn"
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
                class="flex border-b border-[var(--rp-chat-sidebar-border)] px-1 py-2"
                role="tablist"
                aria-label="Вкладки панелі чату"
                @keydown="onSidebarTabKeydown"
            >
                <button
                    v-for="tab in sidebarTabs"
                    :key="tab.id"
                    :id="'chat-tab-' + tab.id"
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

            <div class="min-h-0 flex-1 overflow-y-auto p-3 text-sm text-[var(--rp-chat-sidebar-fg)]">
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
                    aria-labelledby="chat-tab-users"
                    tabindex="-1"
                    :aria-hidden="sidebarTab === 'users' ? 'false' : 'true'"
                >
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">
                        Онлайн
                    </p>
                    <ul v-if="user" class="space-y-2">
                        <li
                            class="rp-chat-side-card flex items-center gap-2 rounded-md border px-2 py-2"
                        >
                            <UserAvatar
                                :src="user.avatar_url || ''"
                                :name="user.user_name"
                                variant="sidebar"
                                decorative
                            />
                            <span class="font-medium text-[var(--rp-chat-sidebar-fg)]">{{ user.user_name }}</span>
                            <span class="text-xs text-[var(--rp-chat-sidebar-muted)]">(ви)</span>
                        </li>
                    </ul>
                    <p class="mt-3 text-[var(--rp-chat-sidebar-muted)]">
                        Інших учасників онлайн поки не показуємо — з’явиться разом із presence API.
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
                    aria-labelledby="chat-tab-friends"
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
                                v-for="f in friendsAccepted"
                                :key="f.user.id"
                                class="rp-chat-side-card flex flex-wrap items-center justify-between gap-2 rounded-md border px-2 py-2"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <UserAvatar
                                        :name="f.user.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                    <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                        f.user.user_name
                                    }}</span>
                                </span>
                                <button
                                    type="button"
                                    class="rp-focusable rp-btn rp-btn-ghost text-sm"
                                    @click="openPrivatePeer(f.user)"
                                >
                                    Приват
                                </button>
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
                                v-for="r in friendsIncoming"
                                :key="'in-' + r.user.id"
                                class="rp-chat-side-card flex flex-wrap items-center gap-2 rounded-md border px-2 py-2"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <UserAvatar
                                        :name="r.user.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                    <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                        r.user.user_name
                                    }}</span>
                                </span>
                                <button
                                    type="button"
                                    class="rp-focusable rp-btn rp-btn-primary text-xs"
                                    @click="acceptFriend(r.user.id)"
                                >
                                    Прийняти
                                </button>
                                <button
                                    type="button"
                                    class="rp-focusable rp-btn rp-btn-ghost text-xs"
                                    @click="rejectFriend(r.user.id)"
                                >
                                    Відхилити
                                </button>
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
                                v-for="r in friendsOutgoing"
                                :key="'out-' + r.user.id"
                                class="flex items-center gap-2 text-sm text-[var(--rp-chat-sidebar-fg)]"
                            >
                                <UserAvatar
                                    :name="r.user.user_name"
                                    variant="sidebar"
                                    decorative
                                />
                                <span class="truncate">{{ r.user.user_name }}</span>
                            </li>
                        </ul>
                    </template>
                </div>

                <!-- Приват -->
                <div
                    v-show="sidebarTab === 'private'"
                    id="chat-panel-private"
                    role="tabpanel"
                    aria-labelledby="chat-tab-private"
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
                        <li v-for="(c, idx) in conversations" :key="conversationRowKey(c, idx)">
                            <button
                                v-if="c.peer && c.peer.id"
                                type="button"
                                class="rp-focusable rp-chat-side-room-btn flex w-full items-start gap-2 rounded-md border-2 px-3 py-2 text-left"
                                @click="openPrivatePeer(c.peer)"
                            >
                                <UserAvatar
                                    :name="c.peer.user_name"
                                    variant="sidebar"
                                    decorative
                                />
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold text-[var(--rp-chat-sidebar-fg)]">{{
                                        c.peer.user_name
                                    }}</span>
                                    <span
                                        class="mt-0.5 block truncate text-xs text-[var(--rp-chat-sidebar-muted)]"
                                    >{{ (c.last_message && c.last_message.body) || '—' }}</span>
                                </span>
                            </button>
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
                    aria-labelledby="chat-tab-rooms"
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
                    aria-labelledby="chat-tab-ignore"
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
                            v-for="row in ignores"
                            :key="row.user.id"
                            class="rp-chat-side-card flex flex-wrap items-center justify-between gap-2 rounded-md border px-2 py-2"
                        >
                            <span class="flex min-w-0 items-center gap-2">
                                <UserAvatar
                                    :name="row.user.user_name"
                                    variant="sidebar"
                                    decorative
                                />
                                <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                    row.user.user_name
                                }}</span>
                            </span>
                            <button
                                type="button"
                                class="rp-focusable text-sm font-semibold text-[var(--rp-chat-sidebar-link)] hover:text-[var(--rp-chat-sidebar-link-hover)] hover:underline"
                                @click="removeIgnore(row.user.id)"
                            >
                                Зняти
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
        </div>

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
import PrivateChatPanel from '../components/PrivateChatPanel.vue';
import UserAvatar from '../components/UserAvatar.vue';
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
        client_message_id: raw.client_message_id,
        avatar: raw.avatar ? String(raw.avatar) : '',
        file,
        image,
    };
}

export default {
    name: 'ChatRoom',
    components: {
        PrivateChatPanel,
        UserAvatar,
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
            ...createChatRoomSidebarState(),
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
    },
    watch: {
        panelOpen() {
            this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
        },
        isNarrowViewport() {
            this.syncBodyScrollLock(this.panelOpen && this.isNarrowViewport);
        },
        sidebarTab(to) {
            if (to === 'private') {
                this.loadConversations();
            }
            if (to === 'friends' || to === 'ignore') {
                this.loadFriendsAndIgnores();
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
                const btn = this.$refs.panelCloseBtn;
                if (btn && typeof btn.focus === 'function') {
                    btn.focus();
                }
            });
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
                const el = document.getElementById(`chat-tab-${id}`);
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
            this.echoSubscribedRoomId = roomId;
            const channel = echo.private(`room.${roomId}`);

            channel.subscribed(() => {
                this.wsDegraded = false;
                this.stopPoll();
            });

            channel.error(() => {
                this.wsDegraded = true;
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
        conversationRowKey(c, idx) {
            if (c && c.peer && c.peer.id != null) {
                return `peer-${c.peer.id}`;
            }

            return `conv-${idx}`;
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
            const msgMatch = text.match(/^\/msg\s+(\S+)(?:\s+(.*))?$/i);
            if (msgMatch) {
                const targetName = msgMatch[1];
                const pmBody = (msgMatch[2] || '').trim();
                this.sending = true;
                this.loadError = '';
                await this.ensureSanctum();
                try {
                    const { data } = await window.axios.get('/api/v1/users/lookup', {
                        params: { name: targetName },
                    });
                    this.openPrivatePeer(data.data);
                    this.composerText = '';
                    if (pmBody) {
                        await this.sendPrivateMessageFromPanel(pmBody);
                    }
                } catch (e) {
                    this.loadError = e.response?.data?.message || 'Не вдалося знайти користувача для /msg.';
                } finally {
                    this.sending = false;
                }

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
