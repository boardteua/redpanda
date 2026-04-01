export function isStaffRole(role) {
    return role === 'moderator' || role === 'admin';
}

/**
 * Пункти меню дій користувача (сайдбар) — спільна матриця T22 для інлайн-панелі та контекстного меню.
 *
 * @returns {({ type: 'btn', id: string, label: string, focusIndex: number }|{ type: 'sep' })[]}
 */
export function buildUserBadgeMenuItems(mode, viewer, target) {
    const v = viewer;
    const t = target;
    const items = [];
    let fi = 0;
    const add = (id, label) => {
        items.push({ type: 'btn', id, label, focusIndex: fi });
        fi += 1;
    };
    const sep = () => {
        items.push({ type: 'sep' });
    };

    if (mode === 'self') {
        add('info', 'Інформація');
        if (v && v.chat_role === 'admin') {
            add('commands', 'Команди');
            add('admin-hub', 'Панель адміна');
        }
        if (v && v.chat_role !== 'admin' && isStaffRole(v.chat_role)) {
            add('staff-stop-words', 'Стоп-слова / фільтр');
            add('staff-flagged', 'Черга на модерацію');
        }
        if (v && !v.guest) {
            add('profile', 'Профіль');
        }
    } else if (t) {
        add('info', 'Інформація');
        add('private', 'Приватний чат');
        const hideIgnoreStaffTarget = isStaffRole(t.chat_role) && v && !isStaffRole(v.chat_role);
        if (!hideIgnoreStaffTarget) {
            add('ignore', 'Ігнор');
        }
        if (v && !t.guest) {
            const rel = t.friendship;
            if (rel === 'accepted') {
                add('unfriend', 'Прибрати з друзів');
            } else if (rel === 'pending_out') {
                add('cancel-friend', 'Скасувати запит у друзі');
            } else if (rel === 'pending_in') {
                add('accept-friend', 'Прийняти запит у друзі');
                add('reject-friend', 'Відхилити запит');
            } else {
                add('friend', 'Додати до друзів');
            }
        }
        if (v && isStaffRole(v.chat_role) && t.id != null && v.id != null && Number(t.id) !== Number(v.id)) {
            sep();
            add('mute', 'Кляп…');
            add('kick', 'Вигнати…');
        }
    }

    return items.filter((row) => row.type === 'sep' || row.type === 'btn');
}
