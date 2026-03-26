<template>
    <RpModal
        :open="open"
        variant="framed"
        size="lg"
        max-height-class="max-h-[min(92vh,36rem)]"
        aria-labelledby="user-info-title"
        :scroll-body="false"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-2 py-1">
                <h2 id="user-info-title" class="text-base font-semibold text-[var(--rp-text)]">
                    {{ title }}
                </h2>
                <RpCloseButton @click="close" />
            </div>
        </template>
        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-2 text-sm text-[var(--rp-text)]">
            <template v-if="mode === 'self'">
                <p v-if="!viewer" class="text-[var(--rp-text-muted)]">
                    Завантаження профілю…
                </p>
                <template v-else>
                    <div class="flex justify-center pb-2">
                        <UserAvatar
                            :src="viewer.avatar_url || ''"
                            :name="viewer.user_name"
                            variant="modal"
                            :decorative="false"
                        />
                    </div>
                    <p>
                        <span class="font-medium">Нік:</span>
                        {{ viewer.user_name }}
                    </p>
                    <p v-if="!viewer.guest">
                        <span class="font-medium">E-mail:</span>
                        {{ viewer.email || '—' }}
                    </p>
                    <p>
                        <span class="font-medium">Роль у чаті:</span>
                        {{ roleLabel(viewer.chat_role) }}
                    </p>
                    <p
                        v-if="!viewer.guest && viewer.chat_upload_disabled"
                        role="status"
                        class="rounded-md border border-amber-600/40 bg-amber-500/10 px-2 py-2 text-xs text-[var(--rp-text)]"
                    >
                        Модератор вимкнув завантаження зображень у чаті та зміну аватарки.
                    </p>
                    <p v-if="viewer.guest">
                        <span class="text-[var(--rp-text-muted)]">Ви увійшли як гість.</span>
                    </p>
                    <template v-if="!viewer.guest">
                        <UserInfoProfileBlock :profile="viewer.profile" />
                        <UserInfoSocialBlock :links="resolvedSocialLinksForUser(viewer)" />
                    </template>
                    <div
                        v-if="viewer.guest"
                        class="mt-4 space-y-2 rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-surface-elevated)] p-3"
                    >
                        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                            Оформлення
                        </p>
                        <p class="text-xs text-[var(--rp-text-muted)]">
                            Тема зберігається лише на цьому пристрої (без облікового запису).
                        </p>
                        <RpButton
                            variant="secondary"
                            class="w-full text-sm"
                            aria-label="Перемкнути тему оформлення"
                            @click="$emit('cycle-theme')"
                        >
                            {{ themeLabel }}
                        </RpButton>
                    </div>
                </template>
            </template>
            <template v-else>
                <p v-if="!target" class="text-[var(--rp-text-muted)]">
                    Немає даних про користувача.
                </p>
                <template v-else>
                    <div class="flex justify-center pb-2">
                        <UserAvatar
                            :src="displayUser.avatar_url || ''"
                            :name="displayUser.user_name"
                            variant="modal"
                            :decorative="false"
                        />
                    </div>
                    <p>
                        <span class="font-medium">Нік:</span>
                        {{ displayUser.user_name }}
                    </p>
                    <p v-if="limitedGuestView">
                        <span class="text-[var(--rp-text-muted)]">
                            Обліковий запис гостя. Додаткова інформація для звичайних користувачів не показується.
                        </span>
                    </p>
                    <template v-else>
                        <p v-if="cardLoading" class="text-[var(--rp-text-muted)]">
                            Завантаження профілю…
                        </p>
                        <p
                            v-else-if="cardError"
                            role="alert"
                            class="rounded-md border border-amber-600/40 bg-amber-500/10 px-2 py-2 text-xs text-[var(--rp-text)]"
                        >
                            {{ cardError }}
                        </p>
                        <template v-if="!target.guest && !cardLoading">
                            <p>
                                <span class="font-medium">Роль:</span>
                                {{ roleLabel(displayUser.chat_role) }}
                            </p>
                            <p
                                v-if="!target.guest && displayUser.chat_upload_disabled"
                                role="status"
                                class="rounded-md border border-amber-600/40 bg-amber-500/10 px-2 py-2 text-xs text-[var(--rp-text)]"
                            >
                                У цього користувача вимкнено завантаження зображень у чаті.
                            </p>
                            <UserInfoProfileBlock
                                v-if="!cardError || fetchedCard"
                                :profile="displayUser.profile"
                            />
                            <UserInfoSocialBlock :links="resolvedSocialLinksForUser(displayUser)" />
                        </template>
                        <div
                            v-if="viewerStaff && target"
                            class="space-y-2 rounded-md border border-dashed border-[var(--rp-border-subtle)] bg-[var(--rp-surface-elevated)] p-3 text-xs text-[var(--rp-text-muted)]"
                        >
                            <p class="font-semibold text-[var(--rp-text)]">Мережеві дані (персонал)</p>
                            <p v-if="networkInsightLoading" class="mt-1">Завантаження…</p>
                            <p v-else-if="networkInsightError" class="mt-1 text-amber-700 dark:text-amber-400" role="alert">
                                {{ networkInsightError }}
                            </p>
                            <template v-else-if="networkInsight">
                                <p v-if="!networkInsight.latest_session && networkInsight.sessions_sampled === 0" class="mt-1">
                                    Немає збережених сесій з IP (таблиця <code class="rounded bg-[var(--rp-surface)] px-1">sessions</code>
                                    порожня або інший драйвер сесій).
                                </p>
                                <template v-else>
                                    <p v-if="networkInsight.latest_session" class="mt-1 space-y-1 text-[var(--rp-text)]">
                                        <span class="block">
                                            <span class="font-medium">Остання IP:</span>
                                            {{ networkInsight.latest_session.ip_address }}
                                        </span>
                                        <span class="block text-[var(--rp-text-muted)]">
                                            {{ formatNetworkTime(networkInsight.latest_session.last_activity_at) }}
                                        </span>
                                        <span
                                            v-if="networkInsight.latest_session.user_agent"
                                            class="block break-all text-[var(--rp-text-muted)]"
                                        >
                                            <span class="font-medium text-[var(--rp-text)]">User-Agent:</span>
                                            {{ networkInsight.latest_session.user_agent }}
                                        </span>
                                    </p>
                                    <div v-if="networkInsight.recent_ips && networkInsight.recent_ips.length" class="mt-2">
                                        <p class="font-medium text-[var(--rp-text)]">IP у вибірці сесій</p>
                                        <ul class="mt-1 list-inside list-disc space-y-1">
                                            <li v-for="row in networkInsight.recent_ips" :key="row.ip">
                                                <span class="font-mono text-[var(--rp-text)]">{{ row.ip }}</span>
                                                <span v-if="row.banned" class="ml-1 rounded bg-red-600/15 px-1 text-red-700 dark:text-red-400">
                                                    у бані
                                                </span>
                                                <span class="block text-[var(--rp-text-muted)]">
                                                    {{ formatNetworkTime(row.last_seen_at) }}
                                                </span>
                                            </li>
                                        </ul>
                                    </div>
                                </template>
                            </template>
                        </div>
                    </template>
                </template>
            </template>
        </div>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';
import UserAvatar from './UserAvatar.vue';
import UserInfoProfileBlock from './UserInfoProfileBlock.vue';
import UserInfoSocialBlock from './UserInfoSocialBlock.vue';
import { buildUserInfoSocialLinks } from '../utils/socialLinks.js';

function isStaffRole(role) {
    return role === 'moderator' || role === 'admin';
}

export default {
    name: 'UserInfoModal',
    components: { RpModal, UserAvatar, UserInfoProfileBlock, UserInfoSocialBlock },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        mode: {
            type: String,
            default: 'self',
        },
        viewer: {
            type: Object,
            default: null,
        },
        target: {
            type: Object,
            default: null,
        },
        themeLabel: {
            type: String,
            default: '',
        },
        /** Поточна кімната чату — для GET profile-card (T148). */
        roomContextRoomId: {
            type: [Number, String],
            default: null,
        },
    },
    data() {
        return {
            networkInsight: null,
            networkInsightError: null,
            networkInsightLoading: false,
            fetchedCard: null,
            cardLoading: false,
            cardError: '',
        };
    },
    computed: {
        title() {
            return this.mode === 'self' ? 'Ваш профіль у чаті' : 'Інформація про користувача';
        },
        viewerStaff() {
            const v = this.viewer;

            return v && isStaffRole(v.chat_role);
        },
        limitedGuestView() {
            if (this.mode !== 'other' || !this.target || !this.target.guest) {
                return false;
            }

            return !this.viewerStaff;
        },
        shouldFetchNetworkInsight() {
            return (
                this.open &&
                this.mode === 'other' &&
                this.viewerStaff &&
                this.target &&
                this.target.id != null
            );
        },
        displayUser() {
            if (this.mode === 'self') {
                return this.viewer;
            }
            if (!this.target) {
                return null;
            }
            if (this.fetchedCard) {
                return { ...this.target, ...this.fetchedCard };
            }

            return this.target;
        },
    },
    watch: {
        open(val) {
            if (!val) {
                this.networkInsight = null;
                this.networkInsightError = null;
                this.networkInsightLoading = false;
                this.fetchedCard = null;
                this.cardLoading = false;
                this.cardError = '';

                return;
            }
            this.loadPeerCard();
            if (this.shouldFetchNetworkInsight) {
                this.loadNetworkInsight();
            }
        },
        target: {
            deep: true,
            handler() {
                if (this.open) {
                    this.loadPeerCard();
                }
                if (this.open && this.shouldFetchNetworkInsight) {
                    this.loadNetworkInsight();
                }
            },
        },
        viewer: {
            deep: true,
            handler() {
                if (this.open) {
                    this.loadPeerCard();
                }
                if (this.open && this.shouldFetchNetworkInsight) {
                    this.loadNetworkInsight();
                }
            },
        },
        roomContextRoomId() {
            if (this.open) {
                this.loadPeerCard();
            }
        },
    },
    methods: {
        resolvedSocialLinksForUser(u) {
            if (!u || u.guest) {
                return [];
            }

            return buildUserInfoSocialLinks(u.social_links);
        },
        async loadPeerCard() {
            this.cardError = '';
            if (!this.open || this.mode !== 'other' || !this.target || this.target.guest) {
                this.fetchedCard = null;
                this.cardLoading = false;

                return;
            }
            if (!this.viewer || this.viewer.guest) {
                this.fetchedCard = null;
                this.cardError = 'Увійдіть під обліковим записом, щоб переглядати профіль.';

                return;
            }
            if (this.roomContextRoomId == null || this.roomContextRoomId === '') {
                this.fetchedCard = null;
                this.cardError = 'Оберіть кімнату, щоб завантажити профіль користувача.';

                return;
            }

            const uid = this.target.id;
            if (uid == null) {
                return;
            }

            this.cardLoading = true;
            this.fetchedCard = null;
            try {
                const rid = encodeURIComponent(String(this.roomContextRoomId));
                const { data } = await window.axios.get(`/api/v1/rooms/${rid}/users/${encodeURIComponent(String(uid))}/profile-card`);
                if (data && data.data) {
                    this.fetchedCard = data.data;
                }
            } catch (e) {
                const msg =
                    (e.response && e.response.data && e.response.data.message) ||
                    (e.response && e.response.status === 403
                        ? 'Недостатньо прав для перегляду профілю в цій кімнаті.'
                        : 'Не вдалося завантажити профіль.');
                this.cardError = typeof msg === 'string' ? msg : 'Не вдалося завантажити профіль.';
            } finally {
                this.cardLoading = false;
            }
        },
        async loadNetworkInsight() {
            if (!this.shouldFetchNetworkInsight || typeof window === 'undefined' || !window.axios) {
                return;
            }
            const id = this.target.id;
            this.networkInsightLoading = true;
            this.networkInsightError = null;
            this.networkInsight = null;
            try {
                const { data } = await window.axios.get(`/api/v1/mod/users/${id}/network-insight`);
                this.networkInsight = data.data || null;
            } catch (e) {
                const msg =
                    (e.response && e.response.data && e.response.data.message) ||
                    (e.response && e.response.status === 403
                        ? 'Недостатньо прав для перегляду мережевих даних.'
                        : 'Не вдалося завантажити мережеві дані.');
                this.networkInsightError = typeof msg === 'string' ? msg : 'Не вдалося завантажити мережеві дані.';
            } finally {
                this.networkInsightLoading = false;
            }
        },
        formatNetworkTime(iso) {
            if (!iso) {
                return '';
            }
            try {
                const d = new Date(iso);

                return Number.isNaN(d.getTime()) ? iso : d.toLocaleString('uk-UA');
            } catch {
                return iso;
            }
        },
        close() {
            this.$emit('close');
        },
        roleLabel(role) {
            const map = {
                guest: 'Гість',
                user: 'Користувач',
                vip: 'VIP',
                moderator: 'Модератор',
                admin: 'Адміністратор',
            };

            return map[role] || role || '—';
        },
    },
};
</script>
