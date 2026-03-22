import './bootstrap';
import Vue from 'vue';
import App from './App.vue';
import UserAvatar from './components/UserAvatar.vue';
import RpBanner from './components/ui/RpBanner.vue';
import RpButton from './components/ui/RpButton.vue';
import RpPanel from './components/ui/RpPanel.vue';
import RpTextField from './components/ui/RpTextField.vue';
import router from './router';

/** Глобально: уникнути «Unknown custom element» у будь-якому view без локального components. */
Vue.component('UserAvatar', UserAvatar);
Vue.component('RpBanner', RpBanner);
Vue.component('RpButton', RpButton);
Vue.component('RpPanel', RpPanel);
Vue.component('RpTextField', RpTextField);

new Vue({
    router,
    render: (h) => h(App),
}).$mount('#app');
