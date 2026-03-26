<template>
    <div
        v-if="links.length"
        class="space-y-2 border-t border-[var(--rp-border-subtle)] pt-3 mt-2"
    >
        <p class="text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
            Соцмережі
        </p>
        <ul class="m-0 list-none space-y-1.5 p-0">
            <li v-for="l in links" :key="l.key">
                <a
                    class="rp-focusable text-[var(--rp-link)] underline underline-offset-2"
                    :href="l.href"
                    target="_blank"
                    rel="noopener noreferrer"
                >{{ l.label }}</a>
            </li>
        </ul>
    </div>
</template>

<script>
export default {
    name: 'UserInfoSocialBlock',
    props: {
        links: {
            type: Array,
            default: () => [],
        },
    },
};
</script>
