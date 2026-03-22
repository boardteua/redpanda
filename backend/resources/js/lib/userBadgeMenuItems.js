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
        add('commands', 'Команди');
        if (v && v.chat_role === 'admin') {
            add('settings', 'Налаштування чату');
        }
        if (v && !v.guest) {
            add('profile', 'Профіль');
        }
    } else if (t) {
        add('info', 'Інформація');
        add('private', 'Приватний чат');
        add('ignore', 'Ігнор');
        if (v && !t.guest) {
            add('friend', 'Додати до друзів');
        }
        if (v && isStaffRole(v.chat_role) && t.id != null && v.id != null && Number(t.id) !== Number(v.id)) {
            sep();
            add('mute', 'Кляп…');
            add('kick', 'Вигнати…');
        }
    }

    return items.filter((row) => row.type === 'sep' || row.type === 'btn');
}
