import Vue from 'vue';

/**
 * Глобальний шар неблокуючих сповіщень (T87): помилки, попередження, прогрес upload.
 * Використання: `showError` / `showWarning` / `showProgress` з `components/ui/RpToastStack.vue` у корені додатку.
 */

export const rpToastState = Vue.observable({
    /** @type {{ id: number, kind: 'error'|'warning'|'progress', message: string, percent: number|null }[]} */
    items: [],
});

let seq = 0;
/** @type {Map<number, ReturnType<typeof setTimeout>>} */
const dismissTimers = new Map();

const MAX_VISIBLE = 8;

function nextId() {
    seq += 1;
    return seq;
}

export function dismissToast(id) {
    const t = dismissTimers.get(id);
    if (t != null) {
        clearTimeout(t);
        dismissTimers.delete(id);
    }
    const idx = rpToastState.items.findIndex((x) => x.id === id);
    if (idx >= 0) {
        rpToastState.items.splice(idx, 1);
    }
}

/**
 * @param {{ kind: 'error'|'warning'|'progress', message: string, percent?: number|null, id?: number }} partial
 * @returns {number}
 */
function pushToast(partial) {
    const id = partial.id ?? nextId();
    const item = {
        id,
        kind: partial.kind,
        message: partial.message,
        percent: partial.percent == null ? null : partial.percent,
    };
    rpToastState.items.push(item);
    while (rpToastState.items.length > MAX_VISIBLE) {
        dismissToast(rpToastState.items[0].id);
    }

    return id;
}

/**
 * @param {string} message
 * @param {{ durationMs?: number }} [opts] — 0 = без автозникнення
 * @returns {number} id тоста
 */
export function showError(message, opts = {}) {
    const id = pushToast({
        kind: 'error',
        message: String(message || 'Помилка'),
        percent: null,
    });
    const ms = opts.durationMs !== undefined ? opts.durationMs : 8000;
    if (ms > 0) {
        dismissTimers.set(id, setTimeout(() => dismissToast(id), ms));
    }

    return id;
}

/**
 * @param {string} message
 * @param {{ durationMs?: number }} [opts]
 * @returns {number}
 */
export function showWarning(message, opts = {}) {
    const id = pushToast({
        kind: 'warning',
        message: String(message || ''),
        percent: null,
    });
    const ms = opts.durationMs !== undefined ? opts.durationMs : 6000;
    if (ms > 0) {
        dismissTimers.set(id, setTimeout(() => dismissToast(id), ms));
    }

    return id;
}

/**
 * Прогрес завантаження (XHR). Повертає керування для оновлення % і закриття.
 * Документація Axios: onUploadProgress отримує нативний progress event (loaded/total у браузері).
 *
 * @param {string} message
 * @param {{ indeterminate?: boolean }} [opts] — за замовчуванням indeterminate до першого setPercent
 * @returns {{ id: number, setPercent: (n: number) => void, done: () => void, fail: (msg?: string) => void }}
 */
export function showProgress(message, opts = {}) {
    const indeterminateDefault = opts.indeterminate !== false;
    const id = pushToast({
        kind: 'progress',
        message: String(message || '…'),
        percent: indeterminateDefault ? null : 0,
    });

    return {
        id,
        setPercent(p) {
            const it = rpToastState.items.find((x) => x.id === id);
            if (!it || it.kind !== 'progress') {
                return;
            }
            const n = Math.max(0, Math.min(100, Math.round(Number(p))));
            Vue.set(it, 'percent', n);
        },
        done() {
            dismissToast(id);
        },
        fail(msg) {
            dismissToast(id);
            if (msg) {
                showError(msg);
            }
        },
    };
}
