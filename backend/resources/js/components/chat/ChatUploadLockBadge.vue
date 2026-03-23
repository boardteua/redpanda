<template>
    <span
        class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded border border-amber-600/45 text-amber-800 dark:border-amber-500/45 dark:text-amber-300"
        role="img"
        :aria-label="label"
        :title="label"
    >
        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path
                d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"
            />
        </svg>
    </span>
</template>

<script>
export default {
    name: 'ChatUploadLockBadge',
    props: {
        label: { type: String, required: true },
    },
};
</script>
