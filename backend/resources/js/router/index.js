import Vue from 'vue';
import VueRouter from 'vue-router';
import AuthWelcome from '../views/AuthWelcome.vue';
import ChatRoom from '../views/ChatRoom.vue';

Vue.use(VueRouter);

export default new VueRouter({
    mode: 'history',
    routes: [
        {
            path: '/',
            name: 'home',
            component: AuthWelcome,
        },
        {
            path: '/chat',
            name: 'chat',
            component: ChatRoom,
        },
    ],
});
