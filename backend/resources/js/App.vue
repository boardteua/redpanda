<template>
    <div class="rp-app">
        <router-view />
        <RpImageLightbox />
    </div>
</template>

<script>
import RpImageLightbox from './components/RpImageLightbox.vue';

const THEME_KEY = 'redpanda-theme';

export default {
    name: 'App',
    components: { RpImageLightbox },
    mounted() {
        const stored = localStorage.getItem(THEME_KEY) || 'system';
        document.documentElement.setAttribute('data-theme', stored);
    },
};
</script>
