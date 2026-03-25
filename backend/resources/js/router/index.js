import Vue from 'vue';
import VueRouter from 'vue-router';
import ArchiveChat from '../views/ArchiveChat.vue';
import AuthWelcome from '../views/AuthWelcome.vue';
import AuthCallback from '../views/AuthCallback.vue';
import ForgotPassword from '../views/ForgotPassword.vue';
import ResetPassword from '../views/ResetPassword.vue';
import LegacyPasswordSetup from '../views/LegacyPasswordSetup.vue';
import ChatRoom from '../views/ChatRoom.vue';
import AdminHubView from '../views/AdminHubView.vue';
import StaffUsersView from '../views/StaffUsersView.vue';
import StaffStopWordsView from '../views/StaffStopWordsView.vue';
import StaffFlaggedMessagesView from '../views/StaffFlaggedMessagesView.vue';

Vue.use(VueRouter);

/** T135: маршрути, для яких потрібен важкий CSS чату (перший paint міг бути через welcome.css). */
const ROUTES_NEEDING_CHAT_STYLES = new Set([
    'chat',
    'admin-hub',
    'archive',
    'staff-users',
    'staff-stop-words',
    'staff-flagged',
]);

let chatOnlyCssPromise = null;

function loadChatOnlyStyles() {
    if (!chatOnlyCssPromise) {
        chatOnlyCssPromise = import('../../css/chat-only.css');
    }
    return chatOnlyCssPromise;
}

const router = new VueRouter({
    mode: 'history',
    routes: [
        {
            path: '/',
            name: 'home',
            component: AuthWelcome,
        },
        {
            path: '/forgot-password',
            name: 'forgot-password',
            component: ForgotPassword,
            meta: { documentTitle: 'Чат Рудої Панди — відновлення пароля' },
        },
        {
            path: '/reset-password',
            name: 'reset-password',
            component: ResetPassword,
            meta: { documentTitle: 'Чат Рудої Панди — новий пароль' },
        },
        {
            path: '/legacy-password-setup',
            name: 'legacy-password-setup',
            component: LegacyPasswordSetup,
            meta: { documentTitle: 'Чат Рудої Панди — встановлення пароля' },
        },
        {
            path: '/auth/callback',
            name: 'auth-callback',
            component: AuthCallback,
            meta: { documentTitle: 'Чат Рудої Панди — вхід' },
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
            meta: { documentTitle: 'Чат Рудої Панди — адмін-центр' },
        },
        {
            path: '/archive',
            name: 'archive',
            component: ArchiveChat,
            meta: { documentTitle: 'Чат Рудої Панди — архів чату' },
        },
        {
            path: '/chat/staff-users',
            name: 'staff-users',
            component: StaffUsersView,
            meta: { documentTitle: 'Чат Рудої Панди — користувачі (персонал)' },
        },
        {
            path: '/chat/staff-stop-words',
            name: 'staff-stop-words',
            component: StaffStopWordsView,
            meta: { documentTitle: 'Чат Рудої Панди — стоп-слова' },
        },
        {
            path: '/chat/staff-flagged',
            name: 'staff-flagged',
            component: StaffFlaggedMessagesView,
            meta: { documentTitle: 'Чат Рудої Панди — черга модерації' },
        },
    ],
});

router.beforeEach((to, _from, next) => {
    const initial = typeof window !== 'undefined' ? window.__RP_INITIAL_CSS_ENTRY__ : 'chat';
    if (initial === 'welcome' && ROUTES_NEEDING_CHAT_STYLES.has(to.name)) {
        loadChatOnlyStyles()
            .then(() => next())
            .catch((e) => {
                console.error(e);
                next();
            });
        return;
    }
    next();
});

/** T93: поза `/chat` і `/` — статичний заголовок з meta; чат і вітальня виставляють title у view. */
router.afterEach((to) => {
    if (to.name === 'chat' || to.name === 'home') {
        return;
    }
    const t = to.meta && to.meta.documentTitle;
    if (typeof t === 'string' && t.trim() !== '') {
        document.title = t.trim();
    }
});

export default router;
