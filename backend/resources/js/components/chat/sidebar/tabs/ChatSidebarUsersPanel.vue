<template>
    <div
        id="chat-panel-users"
        role="tabpanel"
        :aria-labelledby="panelTabLabelledby('users')"
        tabindex="-1"
        :aria-hidden="active ? 'false' : 'true'"
    >
        <div v-if="user" class="mb-4 md:hidden">
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-1">
                    <div
                        class="rp-presence-row-transition flex min-w-0 flex-1 items-center gap-3 rounded-lg px-3 py-2.5"
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
                            class="rp-presence-dot h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                            :class="presenceDotClass(viewerPresenceStatus)"
                            role="img"
                            :aria-label="'Статус: ' + presenceLabelUa(viewerPresenceStatus)"
                            :title="presenceLabelUa(viewerPresenceStatus)"
                        />
                        <span class="min-w-0 flex-1 truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                            user.user_name
                        }}</span>
                        <span class="ml-auto flex shrink-0 items-center gap-1">
                            <span
                                v-if="viewerSexMetaRow"
                                class="rp-peer-sex"
                                role="img"
                                :aria-label="'Стать: ' + viewerSexMetaRow.label"
                                :title="viewerSexMetaRow.label"
                                >{{ viewerSexMetaRow.glyph }}</span
                            >
                            <ChatUploadLockBadge
                                v-if="viewerChatUploadLocked"
                                label="Завантаження зображень у чаті вимкнено модератором"
                            />
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
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[var(--rp-chat-sidebar-muted)]">Онлайн</p>
        <ul v-if="user" class="hidden space-y-2 md:block">
            <li
                class="rp-presence-row-transition rp-chat-side-card flex flex-col gap-2 rounded-md border px-2 py-2"
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
                            class="rp-presence-dot h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                            :class="presenceDotClass(viewerPresenceStatus)"
                            role="img"
                            :aria-label="'Статус: ' + presenceLabelUa(viewerPresenceStatus)"
                            :title="presenceLabelUa(viewerPresenceStatus)"
                        />
                        <span class="min-w-0 flex-1 truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                            user.user_name
                        }}</span>
                        <span class="shrink-0 text-xs text-[var(--rp-chat-sidebar-muted)]">(ви)</span>
                        <span class="ml-auto flex shrink-0 items-center gap-1">
                            <span
                                v-if="viewerSexMetaRow"
                                class="rp-peer-sex"
                                role="img"
                                :aria-label="'Стать: ' + viewerSexMetaRow.label"
                                :title="viewerSexMetaRow.label"
                                >{{ viewerSexMetaRow.glyph }}</span
                            >
                            <ChatUploadLockBadge
                                v-if="viewerChatUploadLocked"
                                label="Завантаження зображень у чаті вимкнено модератором"
                            />
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
        <ul v-if="roomPresencePeers.length > 0" class="mt-3 space-y-2" aria-label="Інші учасники онлайн">
            <li
                v-for="p in roomPresencePeers"
                :key="'presence-' + p.id"
                class="rp-presence-row-transition rp-chat-side-card flex flex-col rounded-md border px-2 py-2"
                :class="presenceRowClass(peerSidebarStatus(p))"
            >
                <div class="flex items-center gap-1">
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <UserAvatar :src="p.avatar_url || ''" :name="p.user_name" variant="sidebar" decorative />
                        <span
                            class="rp-presence-dot h-2.5 w-2.5 shrink-0 rounded-full border border-[var(--rp-chat-sidebar-border)]"
                            :class="presenceDotClass(peerSidebarStatus(p))"
                            role="img"
                            :aria-label="'Статус ' + p.user_name + ': ' + presenceLabelUa(peerSidebarStatus(p))"
                            :title="presenceLabelUa(peerSidebarStatus(p))"
                        />
                        <span class="min-w-0 flex-1 truncate font-medium text-[var(--rp-chat-sidebar-fg)]">{{
                            p.user_name
                        }}</span>
                        <span v-if="p.guest" class="shrink-0 text-xs text-[var(--rp-chat-sidebar-muted)]">(гість)</span>
                        <span class="ml-auto flex shrink-0 items-center gap-1">
                            <span
                                v-if="peerSexMetaRow(p)"
                                class="rp-peer-sex"
                                role="img"
                                :aria-label="'Стать ' + p.user_name + ': ' + peerSexMetaRow(p).label"
                                :title="peerSexMetaRow(p).label"
                                >{{ peerSexMetaRow(p).glyph }}</span
                            >
                            <ChatUploadLockBadge
                                v-if="staffPeerUploadLocked(p)"
                                :label="'У ' + p.user_name + ' вимкнено завантаження зображень у чаті'"
                            />
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
        <p v-else-if="wsDegraded" class="mt-3 text-xs text-[var(--rp-chat-sidebar-muted)]">
            У режимі опитування список інших учасників онлайн недоступний.
        </p>
        <p v-else class="mt-3 text-xs text-[var(--rp-chat-sidebar-muted)]">Нікого іншого онлайн у цій кімнаті.</p>
        <div class="mt-4 space-y-2 border-t border-[var(--rp-chat-sidebar-border)] pt-3">
            <div class="flex flex-wrap items-start gap-2">
                <div class="relative min-w-[8rem] flex-1">
                    <input
                        id="pm-lookup"
                        role="combobox"
                        :aria-expanded="peerAutocompleteOpen ? 'true' : 'false'"
                        aria-autocomplete="list"
                        :aria-controls="peerAutocompleteOpen ? 'pm-lookup-listbox' : undefined"
                        :aria-activedescendant="peerAutocompleteActiveDescendantId"
                        aria-label="Почати приватний чат"
                        :value="peerLookupName"
                        type="text"
                        maxlength="191"
                        autocomplete="off"
                        class="rp-input rp-focusable w-full"
                        placeholder="Швидкий приват"
                        @input="onPeerLookupInput"
                        @keydown="onPmLookupKeydown"
                    />
                    <ul
                        v-show="peerAutocompleteOpen"
                        id="pm-lookup-listbox"
                        role="listbox"
                        aria-label="Підказки ніків"
                        class="absolute z-20 mt-1 max-h-48 w-full overflow-y-auto rounded-md border border-[var(--rp-chat-sidebar-border)] bg-[var(--rp-chat-sidebar-bg)] py-1 text-[var(--rp-chat-sidebar-fg)] shadow-md"
                    >
                        <li
                            v-if="peerAutocompleteLoading"
                            role="presentation"
                            class="px-2 py-1.5 text-xs text-[var(--rp-chat-sidebar-muted)]"
                        >
                            Завантаження…
                        </li>
                        <template v-else>
                            <li
                                v-for="(row, idx) in peerAutocompleteSuggestions"
                                :id="'pm-ac-' + idx"
                                :key="'pm-ac-' + row.id"
                                role="option"
                                :aria-selected="peerAutocompleteHighlightIndex === idx ? 'true' : 'false'"
                                class="cursor-pointer px-2 py-1.5 text-sm"
                                :class="
                                    peerAutocompleteHighlightIndex === idx
                                        ? 'bg-[var(--rp-chat-sidebar-border)]/40'
                                        : 'hover:bg-[var(--rp-chat-sidebar-border)]/25'
                                "
                                @mousedown.prevent="$emit('pick-peer-autocomplete', row)"
                            >
                                {{ row.user_name }}
                            </li>
                        </template>
                    </ul>
                </div>
                <RpButton class="shrink-0" :disabled="!String(peerLookupName || '').trim()" @click="$emit('lookup-private')">
                    Відкрити
                </RpButton>
            </div>
        </div>
    </div>
</template>

<script>
import UserAvatar from '../../../UserAvatar.vue';
import RpButton from '../../../ui/RpButton.vue';
import UserBadgeInlineActionPanel from '../../../UserBadgeInlineActionPanel.vue';
import SidebarHamburgerTrigger from '../../../SidebarHamburgerTrigger.vue';
import ChatPeerRoleIcon from '../ChatPeerRoleIcon.vue';
import ChatUploadLockBadge from '../ChatUploadLockBadge.vue';
import { isStaffRole } from '../../../../lib/userBadgeMenuItems';
import {
    normalizedPresenceStatus,
    PRESENCE_STATUS_UNKNOWN,
    presenceRowClass,
    presenceDotClass,
    presenceLabelUa,
    sexGlyphAndLabel,
} from '../chatSidebarPresence';

export default {
    name: 'ChatSidebarUsersPanel',
    components: {
        UserAvatar,
        RpButton,
        UserBadgeInlineActionPanel,
        SidebarHamburgerTrigger,
        ChatPeerRoleIcon,
        ChatUploadLockBadge,
    },
    props: {
        active: { type: Boolean, default: false },
        isNarrowViewport: { type: Boolean, default: false },
        user: { type: Object, default: null },
        badgeMenu: { type: Object, default: null },
        isBadgeMenuOpen: { type: Function, required: true },
        roomPresencePeers: { type: Array, default: () => [] },
        peerPresenceStatusByUserId: { type: Object, default: () => ({}) },
        peerPresenceStatusFetchLoading: { type: Boolean, default: false },
        peerSexHintsByUserId: { type: Object, default: () => ({}) },
        viewerPresenceStatus: { type: String, default: 'online' },
        wsDegraded: { type: Boolean, default: false },
        peerLookupName: { type: String, default: '' },
        peerAutocompleteSuggestions: { type: Array, default: () => [] },
        peerAutocompleteHighlightIndex: { type: Number, default: -1 },
        peerAutocompleteLoading: { type: Boolean, default: false },
        peerAutocompleteOpen: { type: Boolean, default: false },
    },
    computed: {
        peerAutocompleteActiveDescendantId() {
            if (
                !this.peerAutocompleteOpen ||
                this.peerAutocompleteHighlightIndex < 0 ||
                !this.peerAutocompleteSuggestions ||
                this.peerAutocompleteSuggestions.length === 0
            ) {
                return undefined;
            }

            return 'pm-ac-' + this.peerAutocompleteHighlightIndex;
        },
        viewerSexMetaRow() {
            if (!this.user || this.user.guest) {
                return null;
            }
            const prof = this.user.profile;
            if (!prof || prof.sex_hidden) {
                return null;
            }

            return sexGlyphAndLabel(prof.sex);
        },
        viewerChatUploadLocked() {
            const u = this.user;

            return Boolean(u && !u.guest && u.chat_upload_disabled);
        },
    },
    methods: {
        presenceRowClass,
        presenceDotClass,
        presenceLabelUa,
        panelTabLabelledby(panelKey) {
            return (this.isNarrowViewport ? 'chat-tab-m-' : 'chat-tab-d-') + panelKey;
        },
        peerSidebarStatus(p) {
            if (!p || p.id == null) {
                return 'online';
            }
            const raw = this.peerPresenceStatusByUserId[String(p.id)];
            if (raw !== undefined && raw !== null) {
                return normalizedPresenceStatus(raw);
            }
            if (this.peerPresenceStatusFetchLoading) {
                return PRESENCE_STATUS_UNKNOWN;
            }

            return 'online';
        },
        peerSexMetaRow(p) {
            if (!p || p.id == null || !this.user || this.user.guest) {
                return null;
            }
            const row = this.peerSexHintsByUserId[String(p.id)];
            if (!row || !row.sex) {
                return null;
            }

            return sexGlyphAndLabel(row.sex);
        },
        staffPeerUploadLocked(p) {
            if (!p || p.id == null || !this.user || this.user.guest || p.guest) {
                return false;
            }
            if (!isStaffRole(this.user.chat_role)) {
                return false;
            }
            const row = this.peerSexHintsByUserId[String(p.id)];

            return Boolean(row && row.chat_upload_disabled);
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
        onPeerLookupInput(e) {
            this.$emit('update:peerLookupName', e.target.value);
        },
        onPmLookupKeydown(e) {
            this.$emit('peer-lookup-keydown', e);
        },
    },
};
</script>

<style scoped src="../chatSidebarPresence.motion.css"></style>
<style scoped>
.rp-presence-row--away,
.rp-presence-row--inactive {
    filter: grayscale(1);
}

.rp-presence-row--inactive {
    opacity: 0.9;
}
</style>
