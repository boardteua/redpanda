/**
 * T103: завантаження друзів/ігнору та дії по API — методи для ChatRoom.vue.
 */
export const chatRoomFriendsIgnoresMethods = {
    async loadFriendsAndIgnores() {
        if (!this.user) {
            return;
        }
        try {
            this.friendsIgnoresLoadError = '';
            const [acc, inc, out, ign] = await Promise.all([
                window.axios.get('/api/v1/friends'),
                window.axios.get('/api/v1/friends/requests/incoming'),
                window.axios.get('/api/v1/friends/requests/outgoing'),
                window.axios.get('/api/v1/ignores'),
            ]);
            const pickList = (res) => {
                const d = res && res.data && res.data.data;

                return Array.isArray(d) ? d : [];
            };
            this.friendsAccepted = pickList(acc);
            this.friendsIncoming = pickList(inc);
            this.friendsOutgoing = pickList(out);
            this.ignores = pickList(ign);
        } catch {
            this.friendsAccepted = [];
            this.friendsIncoming = [];
            this.friendsOutgoing = [];
            this.ignores = [];
            this.friendsIgnoresLoadError = 'Не вдалося завантажити друзів або список ігнору.';
        }
    },
    async acceptFriend(userId) {
        await this.ensureSanctum();
        try {
            await window.axios.post(`/api/v1/friends/${userId}/accept`);
            await this.loadFriendsAndIgnores();
            this.sidebarTab = 'friends';
            this.friendsSubTab = 'active';
        } catch (e) {
            this.loadError = e.response?.data?.message || 'Не вдалося прийняти запит.';
        }
    },
    async rejectFriend(userId) {
        await this.ensureSanctum();
        try {
            await window.axios.post(`/api/v1/friends/${userId}/reject`);
            await this.loadFriendsAndIgnores();
            this.sidebarTab = 'friends';
            this.friendsSubTab = 'pending';
        } catch (e) {
            this.loadError = e.response?.data?.message || 'Не вдалося відхилити запит.';
        }
    },
    async removeIgnore(userId) {
        await this.ensureSanctum();
        try {
            await window.axios.delete(`/api/v1/ignores/${userId}`);
            await this.loadFriendsAndIgnores();
        } catch (e) {
            this.loadError = e.response?.data?.message || 'Не вдалося зняти ігнор.';
        }
    },
};
