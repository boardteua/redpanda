<template>
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
                                    :class="presenceRowClass(viewerPresenceStatus)"
                                    style="background: var(--rp-burger-self-bar-bg)"
                                >
                                    <UserAvatar
                                        :src="user.avatar_url || ''"
                                        :name="user.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                    <span
                                        class="h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                                        :class="presenceDotClass(viewerPresenceStatus)"
                                        role="img"
                                        :aria-label="'Статус: ' + presenceLabelUa(viewerPresenceStatus)"
                                        :title="presenceLabelUa(viewerPresenceStatus)"
                                    />
                                    <span
                                        class="min-w-0 flex-1 truncate font-medium text-[var(--rp-chat-sidebar-fg)]"
                                        >{{ user.user_name }}</span>
                                    <span class="ml-auto flex shrink-0 items-center gap-1">
                                        <span
                                            v-if="viewerSexMetaRow"
                                            class="rp-peer-sex"
                                            role="img"
                                            :aria-label="'Стать: ' + viewerSexMetaRow.label"
                                            :title="viewerSexMetaRow.label"
                                            >{{ viewerSexMetaRow.glyph }}</span>
                                        <ChatPeerRoleIcon :role="user.chat_role" />
                                    </span>
                                </div>
                                <SidebarHamburgerTrigger
                                    :expanded="isBadgeMenuOpen('self-m')"
                                    aria-label="Меню дій для вашого профілю"
                                    @activate="$emit('open-self-badge-menu', $event, 'self-m')"
                                />
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="isBadgeMenuOpen('self-m') && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="null"
                                @pick="$emit('sidebar-badge-pick', $event)"
                                @close="$emit('sidebar-badge-close')"
                            />
                        </div>
                    </div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">
                        Онлайн
                    </p>
                    <ul v-if="user" class="space-y-2">
                        <li
                            class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
                            :class="presenceRowClass(viewerPresenceStatus)"
                        >
                            <div class="flex items-center gap-1">
                                <div class="flex min-w-0 flex-1 items-center gap-2">
                                    <UserAvatar
                                        :src="user.avatar_url || ''"
                                        :name="user.user_name"
                                        variant="sidebar"
                                        decorative
                                    />
                                    <span
                                        class="h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                                        :class="presenceDotClass(viewerPresenceStatus)"
                                        role="img"
                                        :aria-label="'Статус: ' + presenceLabelUa(viewerPresenceStatus)"
                                        :title="presenceLabelUa(viewerPresenceStatus)"
                                    />
                                    <span
                                        class="min-w-0 flex-1 truncate font-medium text-[var(--rp-chat-sidebar-fg)]"
                                        >{{ user.user_name }}</span>
                                    <span class="shrink-0 text-xs text-[var(--rp-chat-sidebar-muted)]">(ви)</span>
                                    <span class="ml-auto flex shrink-0 items-center gap-1">
                                        <span
                                            v-if="viewerSexMetaRow"
                                            class="rp-peer-sex"
                                            role="img"
                                            :aria-label="'Стать: ' + viewerSexMetaRow.label"
                                            :title="viewerSexMetaRow.label"
                                            >{{ viewerSexMetaRow.glyph }}</span>
                                        <ChatPeerRoleIcon :role="user.chat_role" />
                                    </span>
                                </div>
                                <SidebarHamburgerTrigger
                                    :expanded="isBadgeMenuOpen('self-d')"
                                    aria-label="Меню дій для вашого профілю"
                                    @activate="$emit('open-self-badge-menu', $event, 'self-d')"
                                />
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="isBadgeMenuOpen('self-d') && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="null"
                                @pick="$emit('sidebar-badge-pick', $event)"
                                @close="$emit('sidebar-badge-close')"
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
                            :class="presenceRowClass(peerSidebarStatus(p))"
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
                                        class="h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                                        :class="presenceDotClass(peerSidebarStatus(p))"
                                        role="img"
                                        :aria-label="
                                            'Статус ' +
                                            p.user_name +
                                            ': ' +
                                            presenceLabelUa(peerSidebarStatus(p))
                                        "
                                        :title="presenceLabelUa(peerSidebarStatus(p))"
                                    />
                                    <span class="min-w-0 flex-1 truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                        p.user_name
                                    }}</span>
                                    <span
                                        v-if="p.guest"
                                        class="shrink-0 text-xs text-[var(--rp-chat-sidebar-muted)]"
                                    >
                                        (гість)
                                    </span>
                                    <span class="ml-auto flex shrink-0 items-center gap-1">
                                        <span
                                            v-if="peerSexMetaRow(p)"
                                            class="rp-peer-sex"
                                            role="img"
                                            :aria-label="
                                                'Стать ' + p.user_name + ': ' + peerSexMetaRow(p).label
                                            "
                                            :title="peerSexMetaRow(p).label"
                                            >{{ peerSexMetaRow(p).glyph }}</span>
                                        <ChatPeerRoleIcon :role="p.chat_role" />
                                    </span>
                                </div>
                                <SidebarHamburgerTrigger
                                    :expanded="isBadgeMenuOpen(presenceRowKey(p))"
                                    :aria-label="'Меню дій для ' + p.user_name"
                                    @activate="$emit('open-peer-badge-menu', $event, p, presenceRowKey(p))"
                                />
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="isBadgeMenuOpen(presenceRowKey(p)) && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="badgeMenu.target"
                                @pick="$emit('sidebar-badge-pick', $event)"
                                @close="$emit('sidebar-badge-close')"
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
                                :value="peerLookupName"
                                @input="onPeerLookupInput"
                                type="text"
                                maxlength="191"
                                class="rp-input rp-focusable min-w-[8rem] flex-1"
                                placeholder="нік"
                                @keyup.enter="$emit('lookup-private')"
                            />
                            <button
                                type="button"
                                class="rp-focusable rp-btn rp-btn-primary shrink-0"
                                :disabled="!peerLookupName"
                                @click="$emit('lookup-private')"
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
                            @click="$emit('update:friendsSubTab', 'active')"
                        >
                            Мої друзі
                        </button>
                        <button
                            type="button"
                            class="rp-focusable rp-tab flex-1 px-1 text-xs sm:text-sm"
                            :aria-selected="friendsSubTab === 'pending' ? 'true' : 'false'"
                            @click="$emit('update:friendsSubTab', 'pending')"
                        >
                            Запити на дружбу
                        </button>
                    </div>
                    <template v-if="friendsSubTab === 'active'">
                        <p
                            v-if="friendsAccepted.length === 0"
                            class="text-center text-[var(--rp-chat-sidebar-muted)]"
                        >
                            Список друзів порожній.<br />
                            <span class="mt-2 inline-block text-xs text-[var(--rp-chat-sidebar-muted)]">
                                Додайте користувачів через меню «⋯» у списку «Люди».
                            </span>
                        </p>
                        <ul v-else class="space-y-2">
                            <li
                                v-for="f in friendsAcceptedDisplayRows"
                                :key="f.user.id"
                                class="rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
                                :class="f.presenceRowClass"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="flex min-w-0 flex-1 items-center gap-1">
                                        <div class="flex min-w-0 flex-1 items-center gap-2">
                                            <UserAvatar
                                                :name="f.user.user_name"
                                                variant="sidebar"
                                                decorative
                                            />
                                            <span
                                                class="h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                                                :class="f.presenceDotClass"
                                                role="img"
                                                :aria-label="
                                                    'Статус ' + f.user.user_name + ': ' + f.presenceStatusLabel
                                                "
                                                :title="f.presenceStatusLabel"
                                            />
                                            <span class="truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                                                f.user.user_name
                                            }}</span>
                                        </div>
                                        <SidebarHamburgerTrigger
                                            :expanded="isBadgeMenuOpen('friend-' + f.user.id)"
                                            :aria-label="'Меню дій для ' + f.user.user_name"
                                            @activate="$emit('open-peer-badge-menu', $event, f.menuPeer, 'friend-' + f.user.id)"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        class="rp-focusable rp-btn rp-btn-ghost shrink-0 text-sm"
                                        @click="$emit('open-private-peer', f.user)"
                                    >
                                        Приват
                                    </button>
                                </div>
                                <UserBadgeInlineActionPanel
                                    v-if="isBadgeMenuOpen('friend-' + f.user.id) && user"
                                    :mode="badgeMenu.mode"
                                    :viewer="user"
                                    :target="badgeMenu.target"
                                    @pick="$emit('sidebar-badge-pick', $event)"
                                    @close="$emit('sidebar-badge-close')"
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
                                            :expanded="isBadgeMenuOpen('fin-' + r.user.id)"
                                            :aria-label="'Меню дій для ' + r.user.user_name"
                                            @activate="$emit('open-peer-badge-menu', $event, r.menuPeer, 'fin-' + r.user.id)"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        class="rp-focusable rp-btn rp-btn-primary shrink-0 text-xs"
                                        @click="$emit('accept-friend', r.user.id)"
                                    >
                                        Прийняти
                                    </button>
                                    <button
                                        type="button"
                                        class="rp-focusable rp-btn rp-btn-ghost shrink-0 text-xs"
                                        @click="$emit('reject-friend', r.user.id)"
                                    >
                                        Відхилити
                                    </button>
                                </div>
                                <UserBadgeInlineActionPanel
                                    v-if="isBadgeMenuOpen('fin-' + r.user.id) && user"
                                    :mode="badgeMenu.mode"
                                    :viewer="user"
                                    :target="badgeMenu.target"
                                    @pick="$emit('sidebar-badge-pick', $event)"
                                    @close="$emit('sidebar-badge-close')"
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
                                            :expanded="isBadgeMenuOpen('fout-' + r.user.id)"
                                            :aria-label="'Меню дій для ' + r.user.user_name"
                                            @activate="$emit('open-peer-badge-menu', $event, r.menuPeer, 'fout-' + r.user.id)"
                                        />
                                    </div>
                                    <UserBadgeInlineActionPanel
                                        v-if="isBadgeMenuOpen('fout-' + r.user.id) && user"
                                        :mode="badgeMenu.mode"
                                        :viewer="user"
                                        :target="badgeMenu.target"
                                        @pick="$emit('sidebar-badge-pick', $event)"
                                        @close="$emit('sidebar-badge-close')"
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
                                    @click="$emit('open-private-peer', row.c.peer)"
                                >
                                    <span class="flex flex-wrap items-center gap-1.5">
                                        <span class="font-semibold text-[var(--rp-chat-sidebar-fg)]">{{
                                            row.c.peer.user_name
                                        }}</span>
                                        <span
                                            v-if="row.c.unread_count > 0"
                                            class="inline-flex min-h-[1.125rem] min-w-[1.125rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white"
                                            aria-hidden="true"
                                        >{{ formatPrivateUnread(row.c.unread_count) }}</span>
                                    </span>
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
                    <div v-if="user && !user.guest" class="mb-4">
                        <button
                            type="button"
                            class="rp-focusable w-full rounded-md border border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-tab-active-bg)] px-3 py-2.5 text-sm font-semibold text-[var(--rp-chat-sidebar-fg)] hover:bg-[var(--rp-chat-sidebar-border)]/30"
                            @click="$emit('open-add-room')"
                        >
                            Додати кімнату
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="rp-label" for="chat-panel-rooms-search">Пошук кімнат</label>
                        <input
                            id="chat-panel-rooms-search"
                            v-model="roomSearchInput"
                            type="search"
                            autocomplete="off"
                            class="rp-input rp-focusable w-full"
                            placeholder="Назва або опис…"
                            aria-label="Швидкий пошук по кімнатах"
                            aria-describedby="chat-panel-rooms-search-hint"
                        />
                        <p id="chat-panel-rooms-search-hint" class="rp-sr-only">
                            Фільтрує список за назвою та описом кімнати без перезавантаження сторінки.
                        </p>
                    </div>
                    <p v-if="loadingRooms" class="text-[var(--rp-chat-sidebar-muted)]">Завантаження…</p>
                    <p
                        v-else-if="roomSearchNoResults"
                        class="py-6 text-center text-[var(--rp-chat-sidebar-muted)]"
                    >
                        Немає кімнат за запитом
                    </p>
                    <ul v-else class="space-y-2">
                        <li v-for="r in filteredRooms" :key="r.room_id" class="flex items-stretch gap-1">
                            <button
                                type="button"
                                class="rp-focusable rp-chat-side-room-btn min-w-0 flex-1 rounded-md border-2 px-3 py-2 text-left transition-colors"
                                :class="r.room_id === selectedRoomId ? 'is-active' : ''"
                                @click="$emit('select-room', r.room_id)"
                            >
                                <span class="block font-semibold text-[var(--rp-chat-sidebar-fg)]">{{
                                    r.room_name
                                }}</span>
                                <span
                                    v-if="r.topic"
                                    class="mt-0.5 block text-xs text-[var(--rp-chat-sidebar-muted)]"
                                >{{ r.topic }}</span>
                            </button>
                            <button
                                v-if="roomListCanManage(r)"
                                type="button"
                                class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center self-center rounded-md border-2 border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-tab-active-bg)] text-[var(--rp-chat-sidebar-fg)] hover:bg-[var(--rp-chat-sidebar-border)]/25"
                                :aria-label="'Редагувати кімнату «' + r.room_name + '»'"
                                @click.stop="$emit('edit-room', r.room_id)"
                            >
                                <svg
                                    class="h-5 w-5 text-[var(--rp-chat-sidebar-link)]"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"
                                    />
                                </svg>
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
                                        :expanded="isBadgeMenuOpen('ign-' + row.user.id)"
                                        :aria-label="'Меню дій для ' + row.user.user_name"
                                        @activate="$emit('open-peer-badge-menu', $event, row.menuPeer, 'ign-' + row.user.id)"
                                    />
                                </div>
                                <button
                                    type="button"
                                    class="rp-focusable shrink-0 text-sm font-semibold text-[var(--rp-chat-sidebar-link)] hover:text-[var(--rp-chat-sidebar-link-hover)] hover:underline"
                                    @click="$emit('remove-ignore', row.user.id)"
                                >
                                    Зняти
                                </button>
                            </div>
                            <UserBadgeInlineActionPanel
                                v-if="isBadgeMenuOpen('ign-' + row.user.id) && user"
                                :mode="badgeMenu.mode"
                                :viewer="user"
                                :target="badgeMenu.target"
                                @pick="$emit('sidebar-badge-pick', $event)"
                                @close="$emit('sidebar-badge-close')"
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
</template>

<script>
import UserBadgeInlineActionPanel from '../UserBadgeInlineActionPanel.vue';
import SidebarHamburgerTrigger from '../SidebarHamburgerTrigger.vue';
import ChatPeerRoleIcon from './ChatPeerRoleIcon.vue';

export default {
    name: 'ChatRoomSidebar',
    components: {
        UserBadgeInlineActionPanel,
        SidebarHamburgerTrigger,
        ChatPeerRoleIcon,
    },
    props: {
        panelOpen: { type: Boolean, default: false },
        isNarrowViewport: { type: Boolean, default: false },
        sidebarTabs: { type: Array, required: true },
        sidebarTab: { type: String, required: true },
        privateListLoadError: { type: String, default: '' },
        friendsIgnoresLoadError: { type: String, default: '' },
        user: { type: Object, default: null },
        badgeMenu: { type: Object, default: null },
        isBadgeMenuOpen: { type: Function, required: true },
        roomPresencePeers: { type: Array, default: () => [] },
        peerPresenceStatusByUserId: { type: Object, default: () => ({}) },
        peerSexHintsByUserId: { type: Object, default: () => ({}) },
        viewerPresenceStatus: { type: String, default: 'online' },
        wsDegraded: { type: Boolean, default: false },
        peerLookupName: { type: String, default: '' },
        friendsSubTab: { type: String, required: true },
        friendsAccepted: { type: Array, default: () => [] },
        friendsAcceptedWithMenuPeer: { type: Array, default: () => [] },
        friendsIncoming: { type: Array, default: () => [] },
        friendsIncomingWithMenuPeer: { type: Array, default: () => [] },
        friendsOutgoing: { type: Array, default: () => [] },
        friendsOutgoingWithMenuPeer: { type: Array, default: () => [] },
        conversations: { type: Array, default: () => [] },
        privateConversationRows: { type: Array, default: () => [] },
        /** T56: сума непрочитаних вхідних приватних для вкладки «Приват». */
        privateUnreadTotal: { type: Number, default: 0 },
        rooms: { type: Array, default: () => [] },
        loadingRooms: { type: Boolean, default: false },
        selectedRoomId: {
            default: null,
            validator: (v) => v === null || v === undefined || typeof v === 'number',
        },
        ignores: { type: Array, default: () => [] },
        ignoresWithMenuPeer: { type: Array, default: () => [] },
    },
    data() {
        return {
            /** T59: введення до debounce */
            roomSearchInput: '',
            /** T59: значення для фільтра після debounce (мс див. watch) */
            roomSearchDebounced: '',
            roomSearchDebounceTimer: null,
        };
    },
    computed: {
        /** T59: кімнати за назвою та topic (клієнтський фільтр). */
        filteredRooms() {
            const list = this.rooms || [];
            const q = (this.roomSearchDebounced || '').trim().toLowerCase();
            if (!q) {
                return list;
            }

            return list.filter((r) => {
                const name = String(r.room_name || '').toLowerCase();
                const topic = String(r.topic || '').toLowerCase();

                return name.includes(q) || topic.includes(q);
            });
        },
        roomSearchNoResults() {
            if (this.loadingRooms) {
                return false;
            }
            const q = (this.roomSearchDebounced || '').trim();
            const list = this.rooms || [];
            if (!q || list.length === 0) {
                return false;
            }

            return this.filteredRooms.length === 0;
        },
        privateUnreadBadgeText() {
            const n = this.privateUnreadTotal;
            if (!n) {
                return '';
            }

            return n > 99 ? '99+' : String(n);
        },
        viewerSexMetaRow() {
            if (!this.user || this.user.guest) {
                return null;
            }
            const prof = this.user.profile;
            if (!prof || prof.sex_hidden) {
                return null;
            }

            return this.sexGlyphAndLabel(prof.sex);
        },
        /** Один розрахунок статусу на рядок (уникаємо чотирикратного виклику в шаблоні). */
        friendsAcceptedDisplayRows() {
            const list = this.friendsAcceptedWithMenuPeer || [];

            return list.map((f) => {
                const st = this.friendListPresenceStatus(f.user);

                return {
                    ...f,
                    presenceRowClass: this.presenceRowClass(st),
                    presenceDotClass: this.presenceDotClass(st),
                    presenceStatusLabel: this.presenceLabelUa(st),
                };
            });
        },
    },
    watch: {
        roomSearchInput(val) {
            if (this.roomSearchDebounceTimer) {
                clearTimeout(this.roomSearchDebounceTimer);
            }
            this.roomSearchDebounceTimer = setTimeout(() => {
                this.roomSearchDebounced = String(val || '').trim();
                this.roomSearchDebounceTimer = null;
            }, 250);
        },
    },
    beforeDestroy() {
        if (this.roomSearchDebounceTimer) {
            clearTimeout(this.roomSearchDebounceTimer);
        }
    },
    methods: {
        formatPrivateUnread(n) {
            const c = Number(n);
            if (!c || c < 1) {
                return '';
            }

            return c > 99 ? '99+' : String(c);
        },
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
        sexGlyphAndLabel(sex) {
            if (sex === 'male') {
                return { glyph: '\u2642', label: 'Чоловік' };
            }
            if (sex === 'female') {
                return { glyph: '\u2640', label: 'Жінка' };
            }
            if (sex === 'other') {
                return { glyph: '\u26a7', label: 'Інше' };
            }

            return null;
        },
        peerSexMetaRow(p) {
            if (!p || p.id == null || !this.user || this.user.guest) {
                return null;
            }
            const row = this.peerSexHintsByUserId[String(p.id)];
            if (!row || !row.sex) {
                return null;
            }

            return this.sexGlyphAndLabel(row.sex);
        },
        /** T54: олівець у списку — творець кімнати або модератор/адмін. */
        roomListCanManage(room) {
            const u = this.user;
            if (!u || u.guest || !room) {
                return false;
            }
            const role = u.chat_role;
            if (role === 'moderator' || role === 'admin') {
                return true;
            }
            const cid = room.created_by_user_id;
            return cid != null && Number(cid) === Number(u.id);
        },
        panelTabLabelledby(panelKey) {
            return (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + panelKey;
        },
        normalizedPresenceStatus(raw) {
            return raw === 'away' || raw === 'inactive' ? raw : 'online';
        },
        peerSidebarStatus(p) {
            if (!p || p.id == null) {
                return 'online';
            }

            return this.normalizedPresenceStatus(
                this.peerPresenceStatusByUserId[String(p.id)],
            );
        },
        /** T50: у «Мої друзі» показуємо всіх обраних; якщо статусу в кімнаті немає — вважаємо не в мережі (не плутаємо з онлайн). */
        friendListPresenceStatus(u) {
            if (!u || u.id == null) {
                return 'inactive';
            }
            const raw = this.peerPresenceStatusByUserId[String(u.id)];

            return raw === undefined || raw === null
                ? 'inactive'
                : this.normalizedPresenceStatus(raw);
        },
        presenceRowClass(status) {
            const s = this.normalizedPresenceStatus(status);
            if (s === 'inactive') {
                return 'rp-presence-row--inactive';
            }
            if (s === 'away') {
                return 'rp-presence-row--away';
            }

            return '';
        },
        presenceDotClass(status) {
            const s = this.normalizedPresenceStatus(status);
            if (s === 'inactive') {
                return 'bg-gray-500';
            }
            if (s === 'away') {
                return 'bg-amber-500';
            }

            return 'bg-green-600';
        },
        presenceLabelUa(status) {
            const s = this.normalizedPresenceStatus(status);
            if (s === 'away') {
                return 'Відійшов';
            }
            if (s === 'inactive') {
                return 'Неактивний';
            }

            return 'Онлайн';
        },
        presenceRowKey(p) {
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
        onPeerLookupInput(e) {
            this.$emit('update:peerLookupName', e.target.value);
        },
    },
};
</script>

<style scoped>
/* T48: відійшов / неактивний — знебарвлення рядка в списку «Люди». */
.rp-presence-row--away,
.rp-presence-row--inactive {
    filter: grayscale(1);
}

.rp-presence-row--inactive {
    opacity: 0.9;
}
</style>
