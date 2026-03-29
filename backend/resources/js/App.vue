<template>
    <div class="rp-app">
        <router-view />
        <RpImageLightbox />
        <RpToastStack />
        <RpPwaInstallPrompt />
    </div>
</template>

<script>
import RpImageLightbox from './components/RpImageLightbox.vue';
import RpPwaInstallPrompt from './components/RpPwaInstallPrompt.vue';
import RpToastStack from './components/ui/RpToastStack.vue';
import { getResolvedTheme } from './chat/chatRoomConstants';

export default {
    name: 'App',
    components: { RpImageLightbox, RpPwaInstallPrompt, RpToastStack },
    mounted() {
        document.documentElement.setAttribute('data-theme', getResolvedTheme());
    },
};
</script>
