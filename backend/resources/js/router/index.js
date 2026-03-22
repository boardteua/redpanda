import Vue from 'vue';
import VueRouter from 'vue-router';
import ArchiveChat from '../views/ArchiveChat.vue';
import AuthWelcome from '../views/AuthWelcome.vue';
import ChatRoom from '../views/ChatRoom.vue';
import StaffUsersView from '../views/StaffUsersView.vue';

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
        {
            path: '/archive',
            name: 'archive',
            component: ArchiveChat,
        },
        {
            path: '/chat/staff-users',
            name: 'staff-users',
            component: StaffUsersView,
        },
    ],
});
