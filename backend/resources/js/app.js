import './bootstrap';
import Vue from 'vue';
import App from './App.vue';
import UserAvatar from './components/UserAvatar.vue';
import router from './router';

/** Глобально: уникнути «Unknown custom element» у будь-якому view без локального components. */
Vue.component('UserAvatar', UserAvatar);

new Vue({
    router,
    render: (h) => h(App),
}).$mount('#app');
