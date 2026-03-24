<template>
    <RpModal
        :open="open"
        variant="framed"
        size="md"
        max-height-class="max-h-[min(85vh,28rem)]"
        aria-labelledby="user-info-title"
        :scroll-body="false"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3">
                <h2 id="user-info-title" class="text-base font-semibold text-[var(--rp-text)]">
                    {{ title }}
                </h2>
                <RpCloseButton @click="close" />
            </div>
        </template>
        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-3 text-sm text-[var(--rp-text)]">
            <template v-if="mode === 'self'">
                <p v-if="!viewer" class="text-[var(--rp-text-muted)]">
                    Завантаження профілю…
                </p>
                <template v-else>
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
                <!-- T43: гості не мають повного профілю — тема лише локально (localStorage), той самий цикл що в чаті -->
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
                <p>
                    <span class="font-medium">Нік:</span>
                    {{ target.user_name }}
                </p>
                <p v-if="limitedGuestView">
                    <span class="text-[var(--rp-text-muted)]">
                        Обліковий запис гостя. Додаткова інформація для звичайних користувачів не показується.
                    </span>
                </p>
                <template v-else>
                    <p>
                        <span class="font-medium">Роль:</span>
                        {{ roleLabel(target.chat_role) }}
                    </p>
                    <p
                        v-if="!target.guest && target.chat_upload_disabled"
                        role="status"
                        class="rounded-md border border-amber-600/40 bg-amber-500/10 px-2 py-2 text-xs text-[var(--rp-text)]"
                    >
                        У цього користувача вимкнено завантаження зображень у чаті.
                    </p>
                    <p v-if="target.guest">
                        <span class="text-[var(--rp-text-muted)]">Гостьовий сеанс.</span>
                    </p>
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
        </div>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';

function isStaffRole(role) {
    return role === 'moderator' || role === 'admin';
}

export default {
    name: 'UserInfoModal',
    components: { RpModal },
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
    },
    data() {
        return {
            networkInsight: null,
            networkInsightError: null,
            networkInsightLoading: false,
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
    },
    watch: {
        open(val) {
            if (!val) {
                this.networkInsight = null;
                this.networkInsightError = null;
                this.networkInsightLoading = false;

                return;
            }
            if (this.shouldFetchNetworkInsight) {
                this.loadNetworkInsight();
            }
        },
        target: {
            deep: true,
            handler() {
                if (this.open && this.shouldFetchNetworkInsight) {
                    this.loadNetworkInsight();
                }
            },
        },
        viewer: {
            deep: true,
            handler() {
                if (this.open && this.shouldFetchNetworkInsight) {
                    this.loadNetworkInsight();
                }
            },
        },
    },
    methods: {
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
