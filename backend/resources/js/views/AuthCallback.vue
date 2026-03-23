<template>
    <div class="flex min-h-screen items-center justify-center bg-[var(--rp-bg)] px-4">
        <p class="text-sm text-[var(--rp-text-muted)]" role="status">Завершення входу…</p>
    </div>
</template>

<script>
import { ensureAuth0BootstrapFromLandingApi, ensureAuth0Client } from '../lib/rpAuth0';

export default {
    name: 'AuthCallback',
    async mounted() {
        document.documentElement.setAttribute(
            'data-theme',
            typeof localStorage !== 'undefined' ? localStorage.getItem('redpanda-theme') || 'system' : 'system',
        );
        await ensureAuth0BootstrapFromLandingApi();
        const client = await ensureAuth0Client();
        if (!client) {
            await this.$router.replace({ path: '/' }).catch(() => {});

            return;
        }
        try {
            await client.handleRedirectCallback();
            await this.$router.replace({ name: 'chat' }).catch(() => {});
        } catch {
            await this.$router.replace({ path: '/' }).catch(() => {});
        }
    },
};
</script>
