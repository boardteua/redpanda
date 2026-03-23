import Vue from 'vue';
import VueRouter from 'vue-router';
import ArchiveChat from '../views/ArchiveChat.vue';
import AuthWelcome from '../views/AuthWelcome.vue';
import AuthCallback from '../views/AuthCallback.vue';
import ChatRoom from '../views/ChatRoom.vue';
import AdminHubView from '../views/AdminHubView.vue';
import StaffUsersView from '../views/StaffUsersView.vue';
import StaffStopWordsView from '../views/StaffStopWordsView.vue';
import StaffFlaggedMessagesView from '../views/StaffFlaggedMessagesView.vue';

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
            path: '/auth/callback',
            name: 'auth-callback',
            component: AuthCallback,
        },
        {
            path: '/chat',
            name: 'chat',
            component: ChatRoom,
        },
        {
            path: '/chat/admin',
            name: 'admin-hub',
            component: AdminHubView,
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
        {
            path: '/chat/staff-stop-words',
            name: 'staff-stop-words',
            component: StaffStopWordsView,
        },
        {
            path: '/chat/staff-flagged',
            name: 'staff-flagged',
            component: StaffFlaggedMessagesView,
        },
    ],
});
