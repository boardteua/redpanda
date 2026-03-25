import { normalizePostStyleFromApi } from '../utils/chatMessageStyle';

export function peerTargetFromConversationPeerPayload(p) {
    if (!p) {
        return null;
    }

    return {
        id: p.id != null ? Number(p.id) : null,
        user_name: p.user_name != null ? String(p.user_name) : '',
        guest: Boolean(p.guest),
        chat_role: p.chat_role != null ? String(p.chat_role) : 'user',
    };
}

export function peerTargetFromFriendUserPayload(u) {
    if (!u) {
        return null;
    }

    return {
        id: Number(u.id),
        user_name: u.user_name,
        guest: false,
        chat_role: 'user',
    };
}

export function normalizePresencePeer(raw) {
    if (!raw || raw.id === undefined || raw.id === null) {
        return null;
    }

    return {
        id: Number(raw.id),
        user_name: raw.user_name != null ? String(raw.user_name) : '',
        guest: Boolean(raw.guest),
        avatar_url: raw.avatar_url != null ? String(raw.avatar_url) : '',
        chat_role: raw.chat_role != null ? String(raw.chat_role) : 'user',
        badge_color: raw.badge_color != null ? String(raw.badge_color) : '',
        presence_invisible: Boolean(raw.presence_invisible),
    };
}

export function normalizeMessage(raw) {
    if (!raw || typeof raw.post_id === 'undefined') {
        return null;
    }

    const file = raw.file != null ? Number(raw.file) : 0;
    const image =
        raw.image && raw.image.url
            ? { id: Number(raw.image.id), url: raw.image.url }
            : null;

    const base = {
        post_id: raw.post_id,
        post_roomid: raw.post_roomid,
        user_id: raw.user_id,
        post_date:
            raw.post_date != null && raw.post_date !== ''
                ? Number(raw.post_date)
                : raw.post_date,
        post_edited_at:
            raw.post_edited_at != null && raw.post_edited_at !== ''
                ? Number(raw.post_edited_at)
                : null,
        post_deleted_at:
            raw.post_deleted_at != null && raw.post_deleted_at !== ''
                ? Number(raw.post_deleted_at)
                : null,
        post_time: raw.post_time,
        post_user: raw.post_user,
        post_message: raw.post_message,
        post_style: normalizePostStyleFromApi(raw.post_style),
        post_color: raw.post_color,
        type: raw.type,
        recipient_user_id:
            raw.recipient_user_id != null && raw.recipient_user_id !== ''
                ? Number(raw.recipient_user_id)
                : null,
        client_message_id: raw.client_message_id,
        avatar: raw.avatar ? String(raw.avatar) : '',
        file,
        image,
    };
    if (Array.isArray(raw.mentioned_user_ids)) {
        base.mentioned_user_ids = raw.mentioned_user_ids
            .map((x) => Number(x))
            .filter((n) => Number.isFinite(n));
    } else {
        base.mentioned_user_ids = [];
    }
    if (Object.prototype.hasOwnProperty.call(raw || {}, 'can_edit')) {
        base.can_edit = Boolean(raw.can_edit);
    }
    if (Object.prototype.hasOwnProperty.call(raw || {}, 'can_delete')) {
        base.can_delete = Boolean(raw.can_delete);
    }

    return base;
}
