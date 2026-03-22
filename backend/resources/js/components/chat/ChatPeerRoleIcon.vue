<template>
    <span
        v-if="normalized"
        class="rp-peer-role-wrap inline-flex shrink-0 items-center justify-center"
        :class="'rp-peer-role-wrap--' + normalized"
        role="img"
        :aria-label="ariaUa"
    >
        <svg
            v-if="normalized !== 'vip'"
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="currentColor"
            focusable="false"
            aria-hidden="true"
        >
            <path
                d="M12 1 3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4Zm0 2.18 6 2.67V11c0 4.52-2.98 8.69-6 9.81-3.02-1.12-6-5.29-6-9.81V5.85l6-2.67Z"
            />
        </svg>
        <svg
            v-else
            class="h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.2"
            focusable="false"
            aria-hidden="true"
        >
            <circle cx="12" cy="12" r="9" stroke-linecap="round" />
            <path d="M12 8v8M8 12h8" stroke-linecap="round" />
        </svg>
    </span>
</template>

<script>
export default {
    name: 'ChatPeerRoleIcon',
    props: {
        role: {
            type: String,
            default: '',
        },
    },
    computed: {
        normalized() {
            const r = this.role;
            if (r === 'admin' || r === 'moderator' || r === 'vip') {
                return r;
            }

            return '';
        },
        ariaUa() {
            if (this.normalized === 'admin') {
                return 'Адміністратор';
            }
            if (this.normalized === 'moderator') {
                return 'Модератор';
            }
            if (this.normalized === 'vip') {
                return 'VIP';
            }

            return '';
        },
    },
};
</script>
