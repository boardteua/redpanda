import './bootstrap';
import { registerSW } from 'virtual:pwa-register';
import Vue from 'vue';
import VuePortal from '@linusborg/vue-simple-portal';
import App from './App.vue';
import UserAvatar from './components/UserAvatar.vue';
import RpBanner from './components/ui/RpBanner.vue';
import RpButton from './components/ui/RpButton.vue';
import RpCloseButton from './components/ui/RpCloseButton.vue';
import RpPanel from './components/ui/RpPanel.vue';
import RpTextField from './components/ui/RpTextField.vue';
import router from './router';

Vue.use(VuePortal, {
    /** Стабільний id у DOM; контейнер створюється на body при першому порталі (Vue 2 немає Teleport). */
    defaultSelector: 'rp-portal-target',
});

/** Глобально: уникнути «Unknown custom element» у будь-якому view без локального components. */
Vue.component('UserAvatar', UserAvatar);
Vue.component('RpBanner', RpBanner);
Vue.component('RpButton', RpButton);
Vue.component('RpCloseButton', RpCloseButton);
Vue.component('RpPanel', RpPanel);
Vue.component('RpTextField', RpTextField);

if (import.meta.env.PROD) {
    registerSW({ immediate: true });
}

new Vue({
    router,
    render: (h) => h(App),
}).$mount('#app');
