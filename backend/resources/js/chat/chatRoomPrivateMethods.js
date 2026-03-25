import { maybePlayPrivateMessageSound } from '../utils/chatNotificationSounds';
import { setFaviconPrivateUnreadBadge } from '../utils/faviconUnreadBadge';

/**
 * T103: приватні розмови (REST + стан панелі) — методи для ChatRoom.vue.
 * WS-слухачі лишаються в SFC (`ensureUserPrivateListener`).
 */
export const chatRoomPrivateMethods = {
    async markPrivateThreadRead(peerId) {
        if (!peerId || !this.user) {
            return;
        }
        await this.ensureSanctum();
        try {
            await window.axios.post(`/api/v1/private/peers/${peerId}/read`);
        } catch {
            /* не блокуємо UI */
        }
    },
    async loadConversations() {
        if (!this.user) {
            return;
        }
        try {
            this.privateListLoadError = '';
            const { data } = await window.axios.get('/api/v1/private/conversations');
            const list = data && data.data;
            this.conversations = Array.isArray(list) ? list : [];
            const meta = data && data.meta;
            this.totalPrivateUnread =
                meta && typeof meta.total_private_unread === 'number'
                    ? meta.total_private_unread
                    : 0;
        } catch {
            this.conversations = [];
            this.totalPrivateUnread = 0;
            this.privateListLoadError = 'Не вдалося завантажити список розмов.';
        }
        this.$nextTick(() => {
            setFaviconPrivateUnreadBadge(this.totalPrivateUnread);
        });
    },
    async onPrivateWsPayload(payload) {
        if (!payload || typeof payload.id === 'undefined' || !this.user) {
            return;
        }
        if (Number(payload.recipient_id) !== Number(this.user.id)) {
            return;
        }
        if (Number(payload.sender_id) !== Number(this.user.id)) {
            maybePlayPrivateMessageSound(
                this.user,
                Boolean(this.chatSettings && this.chatSettings.silent_mode),
            );
        }
        if (
            this.privatePeer
            && Number(payload.sender_id) === Number(this.privatePeer.id)
        ) {
            this.mergePrivateMessage(payload);
            this.privateMessages.sort((a, b) => a.id - b.id);
            await this.markPrivateThreadRead(this.privatePeer.id);
        }
        await this.loadConversations();
    },
    onPrivateThreadClearedWs(payload) {
        if (!payload || !this.user) {
            return;
        }
        const a = Number(payload.peer_one_id);
        const b = Number(payload.peer_two_id);
        const myId = Number(this.user.id);
        if (!Number.isFinite(a) || !Number.isFinite(b) || (myId !== a && myId !== b)) {
            return;
        }
        const pair = new Set([a, b]);
        if (
            this.privatePeer
            && pair.has(myId)
            && pair.has(Number(this.privatePeer.id))
        ) {
            this.privateMessages = [];
            this.privateMessageIds = new Set();
        }
        this.loadConversations();
    },
    openPrivatePeer(peer) {
        if (!peer || !peer.id) {
            return;
        }
        this.privatePeer = { id: peer.id, user_name: peer.user_name };
        this.privateLoadError = '';
        this.privateMessages = [];
        this.privateMessageIds = new Set();
        this.sidebarTab = 'private';
        this.loadPrivateMessages();
    },
    closePrivatePanel() {
        this.privatePeer = null;
        this.privateMessages = [];
        this.privateMessageIds = new Set();
        this.privateComposerText = '';
        this.privateLoadError = '';
    },
    async lookupAndOpenPrivate() {
        const name = String(this.peerLookupName || '').trim();
        if (!name) {
            return;
        }
        await this.openPrivateByUserName(name);
        this.peerLookupName = '';
    },
    async openPrivateByUserName(name) {
        if (!name || !this.user) {
            return;
        }
        try {
            const { data } = await window.axios.get('/api/v1/users/lookup', {
                params: { name },
            });
            this.openPrivatePeer(data.data);
            this.loadError = '';
        } catch (e) {
            this.loadError = e.response?.data?.message || 'Користувача не знайдено.';
        }
    },
    async loadPrivateMessages() {
        if (!this.privatePeer) {
            return;
        }
        this.loadingPrivateMessages = true;
        this.privateLoadError = '';
        try {
            const { data } = await window.axios.get(
                `/api/v1/private/peers/${this.privatePeer.id}/messages`,
                { params: { limit: 80 } },
            );
            this.privateMessageIds = new Set();
            this.privateMessages = [];
            (data.data || []).forEach((row) => this.mergePrivateMessage(row));
            this.privateMessages.sort((a, b) => a.id - b.id);
            await this.loadConversations();
        } catch (e) {
            this.privateLoadError = e.response?.data?.message || 'Не вдалося завантажити приват.';
        } finally {
            this.loadingPrivateMessages = false;
        }
    },
    mergePrivateMessage(raw) {
        if (!raw || typeof raw.id === 'undefined' || this.privateMessageIds.has(raw.id)) {
            return;
        }
        if (!this.privatePeer || !this.user) {
            return;
        }
        const uid = Number(this.user.id);
        const pid = Number(this.privatePeer.id);
        const sid = Number(raw.sender_id);
        const rid = Number(raw.recipient_id);
        const inThread = (sid === uid && rid === pid) || (sid === pid && rid === uid);
        if (!inThread) {
            return;
        }
        this.privateMessageIds.add(raw.id);
        this.privateMessages.push({
            id: raw.id,
            sender_id: raw.sender_id,
            recipient_id: raw.recipient_id,
            body: raw.body,
            sent_at: raw.sent_at != null && raw.sent_at !== '' ? Number(raw.sent_at) : raw.sent_at,
            sent_time: raw.sent_time,
            client_message_id: raw.client_message_id,
        });
    },
    async clearPrivateThreadFromPanel() {
        if (!this.privatePeer || this.sendingPrivate) {
            return;
        }
        this.sendingPrivate = true;
        this.privateLoadError = '';
        await this.ensureSanctum();
        try {
            await window.axios.delete(`/api/v1/private/peers/${this.privatePeer.id}/thread`);
            this.privateMessages = [];
            this.privateMessageIds = new Set();
            this.privateComposerText = '';
            await this.loadConversations();
        } catch (e) {
            this.privateLoadError =
                e.response?.data?.message || 'Не вдалося очистити приватний тред.';
        } finally {
            this.sendingPrivate = false;
        }
    },
    async sendPrivateMessageFromPanel(body) {
        if (!this.privatePeer || this.sendingPrivate) {
            return;
        }
        const text = typeof body === 'string' ? body.trim() : '';
        if (!text) {
            return;
        }
        if (/^\/clear$/iu.test(text)) {
            await this.clearPrivateThreadFromPanel();
            return;
        }
        this.sendingPrivate = true;
        await this.ensureSanctum();
        const clientMessageId = crypto.randomUUID();
        let privateSendOk = false;
        try {
            const { data, status } = await window.axios.post(
                `/api/v1/private/peers/${this.privatePeer.id}/messages`,
                {
                    message: text,
                    client_message_id: clientMessageId,
                },
            );
            if (data.data) {
                this.mergePrivateMessage(data.data);
                this.privateMessages.sort((a, b) => a.id - b.id);
            }
            if (status === 201 || status === 200) {
                this.privateComposerText = '';
                privateSendOk = true;
            }
            await this.loadConversations();
        } catch (e) {
            this.privateLoadError = e.response?.data?.message || 'Не вдалося надіслати.';
        } finally {
            this.sendingPrivate = false;
            if (privateSendOk) {
                this.$nextTick(() => {
                    const p = this.$refs.privateChatPanel;
                    if (p && typeof p.scheduleFocusComposer === 'function') {
                        p.scheduleFocusComposer();
                    }
                });
            }
        }
    },
};
