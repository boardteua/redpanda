<template>
    <Teleport to="body">
        <div
            v-if="open"
            class="fixed inset-0 z-[75] flex items-end justify-center bg-black/40 p-4 sm:items-center"
            role="presentation"
            @click.self="close"
        >
            <div
                ref="panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="user-info-title"
                class="flex max-h-[min(85vh,28rem)] w-full max-w-md flex-col rounded-lg border border-[var(--rp-border-subtle)] bg-[var(--rp-surface)] shadow-xl"
                tabindex="-1"
                @keydown.stop="onPanelKeydown"
            >
                <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3">
                    <h2 id="user-info-title" class="text-base font-semibold text-[var(--rp-text)]">
                        {{ title }}
                    </h2>
                    <button
                        type="button"
                        class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)]"
                        aria-label="Закрити"
                        @click="close"
                    >
                        <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                            <path
                                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                            />
                        </svg>
                    </button>
                </div>
                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto px-4 py-3 text-sm text-[var(--rp-text)]">
                    <template v-if="mode === 'self'">
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
                        <p v-if="viewer.guest">
                            <span class="text-[var(--rp-text-muted)]">Ви увійшли як гість.</span>
                        </p>
                    </template>
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
                            <p v-if="target.guest">
                                <span class="text-[var(--rp-text-muted)]">Гостьовий сеанс.</span>
                            </p>
                        </template>
                        <div
                            v-if="viewerStaff && target.guest"
                            class="rounded-md border border-dashed border-[var(--rp-border-subtle)] bg-[var(--rp-surface-elevated)] p-3 text-xs text-[var(--rp-text-muted)]"
                        >
                            <p class="font-semibold text-[var(--rp-text)]">Перевірка IP (T23)</p>
                            <p class="mt-1">
                                Тут з’явиться розширена картка та IP-дані після окремого ендпоінта для персоналу. Зараз лише
                                заглушка.
                            </p>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script>
function isStaffRole(role) {
    return role === 'moderator' || role === 'admin';
}

export default {
    name: 'UserInfoModal',
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
    },
    watch: {
        open(v) {
            if (v) {
                document.addEventListener('keydown', this.onDocKeydown);
                this.$nextTick(() => {
                    const p = this.$refs.panel;
                    if (p && typeof p.focus === 'function') {
                        p.focus();
                    }
                });
            } else {
                document.removeEventListener('keydown', this.onDocKeydown);
            }
        },
    },
    beforeDestroy() {
        document.removeEventListener('keydown', this.onDocKeydown);
    },
    methods: {
        close() {
            this.$emit('close');
        },
        onDocKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
            }
        },
        onPanelKeydown(e) {
            if (e.key === 'Escape') {
                e.preventDefault();
                this.close();
            }
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
