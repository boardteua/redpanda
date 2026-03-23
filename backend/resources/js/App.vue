<template>
    <div class="rp-app">
        <router-view />
        <RpImageLightbox />
        <RpToastStack />
    </div>
</template>

<script>
import RpImageLightbox from './components/RpImageLightbox.vue';
import RpToastStack from './components/ui/RpToastStack.vue';

const THEME_KEY = 'redpanda-theme';

export default {
    name: 'App',
    components: { RpImageLightbox, RpToastStack },
    mounted() {
        const stored = localStorage.getItem(THEME_KEY) || 'system';
        document.documentElement.setAttribute('data-theme', stored);
    },
};
</script>
