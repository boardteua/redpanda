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
import { getResolvedTheme } from './chat/chatRoomConstants';

export default {
    name: 'App',
    components: { RpImageLightbox, RpToastStack },
    mounted() {
        document.documentElement.setAttribute('data-theme', getResolvedTheme());
    },
};
</script>
