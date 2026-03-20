<template>
    <div class="rp-app">
        <router-view />
    </div>
</template>

<script>
const THEME_KEY = 'redpanda-theme';

export default {
    name: 'App',
    mounted() {
        const stored = localStorage.getItem(THEME_KEY) || 'system';
        document.documentElement.setAttribute('data-theme', stored);
    },
};
</script>
