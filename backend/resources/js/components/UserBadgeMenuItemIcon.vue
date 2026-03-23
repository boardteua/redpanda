<template>
    <span
        class="inline-flex h-5 w-5 shrink-0 items-center justify-center opacity-90"
        aria-hidden="true"
    >
        <!-- Іконки пунктів rp-sidebar-user-inline-menu (24×24, currentColor). -->
        <svg
            v-if="itemId === 'info'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'commands'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'admin-hub'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'staff-stop-words'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M4.25 5.61C6.27 8.2 10 13 10 13v6c0 .55.45 1 1 1h2c.55 0 1-.45 1-1v-6s3.72-4.8 5.74-7.39c.51-.66.04-1.61-.8-1.61H5.04c-.83 0-1.31.95-.79 1.61z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'staff-flagged'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path d="M14.4 6L14 4H5v17h2v-7h5.6l.4 2h7V6h-5.6z" />
        </svg>
        <svg
            v-else-if="itemId === 'profile'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'private'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6v-2h12v2zm0-3H6V9h12v2zm0-3H6V6h12v2z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'ignore'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM4 12c0-4.42 3.58-8 8-8 1.85 0 3.55.63 4.9 1.69L5.69 16.9C4.63 15.55 4 13.85 4 12zm8 8c-1.85 0-3.55-.63-4.9-1.69L18.31 7.1C19.37 8.45 20 10.15 20 12c0 4.42-3.58 8-8 8z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'unfriend'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M14 8c0-2.21-1.79-4-4-4S6 5.79 6 8s1.79 4 4 4 4-1.79 4-4zm6 12v-2c0-2.21-4.02-4-9.02-4-2.42 0-4.71.35-6 1.05V18h12zM22 11h-8v2h8v-2z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'cancel-friend'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'accept-friend'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'reject-friend'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59 20.59 12 22 13.41 16.41 17 13.41 20.59 12 17 8.41 3.41 12 2z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'friend'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'mute'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M19 11h-1.7c0 .74-.16 1.43-.43 2.05l1.23 1.23c.56-.98.9-2.09.9-3.28zm-4.02.17c0-.06.02-.11.02-.17V5c0-1.66-1.34-3-3-3S9 3.34 9 5v.18l5.98 5.99zM4.27 3L3 4.27 9.01 10.28v.01c0 1.66 1.33 3 2.99 3 .22 0 .44-.03.65-.08l2.4 2.4c-.84.3-1.73.47-2.65.47-2.97 0-5.46-1.9-6.16-4.53H2c.71 2.97 3.35 5.19 6.5 5.19v3h2v-3c1.45 0 2.79-.43 3.96-1.14L20.73 21 22 19.73 4.27 3z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'kick'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'settings'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"
            />
        </svg>
        <svg
            v-else-if="itemId === 'staff-users'"
            class="h-5 w-5"
            viewBox="0 0 24 24"
            fill="currentColor"
        >
            <path
                d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"
            />
        </svg>
        <span v-else class="block h-5 w-5" />
    </span>
</template>

<script>
export default {
    name: 'UserBadgeMenuItemIcon',
    props: {
        itemId: {
            type: String,
            required: true,
        },
    },
};
</script>
