/**
 * Focus trap для модальних діалогів (WCAG: Tab лишається всередині, Escape — окремо в компоненті).
 */

const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

/**
 * @param {HTMLElement} el
 * @returns {boolean}
 */
function isFocusableVisible(el) {
    if (!(el instanceof HTMLElement)) {
        return false;
    }
    if (el.closest('[aria-hidden="true"]')) {
        return false;
    }
    const style = window.getComputedStyle(el);
    if (style.visibility === 'hidden' || style.display === 'none') {
        return false;
    }

    return typeof el.getClientRects === 'function' && el.getClientRects().length > 0;
}

/**
 * Елементи в порядку табуляції всередині контейнера (діалог).
 *
 * @param {HTMLElement} container
 * @returns {HTMLElement[]}
 */
export function getModalFocusables(container) {
    if (!container || typeof container.querySelectorAll !== 'function') {
        return [];
    }

    return Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR)).filter(isFocusableVisible);
}

/**
 * Циклічний Tab / Shift+Tab усередині контейнера. Викликати з keydown на document (capture).
 *
 * @param {KeyboardEvent} e
 * @param {HTMLElement} container
 * @returns {boolean} true якщо подія оброблена (preventDefault викликано)
 */
export function handleModalTabCycle(e, container) {
    if (e.key !== 'Tab' || e.defaultPrevented) {
        return false;
    }

    const focusables = getModalFocusables(container);
    if (focusables.length === 0) {
        e.preventDefault();

        return true;
    }

    const active = document.activeElement;
    const activeInside = active instanceof Node && container.contains(active);
    const activeIsContainer = active === container;

    if (!activeInside || activeIsContainer) {
        e.preventDefault();
        const target = e.shiftKey ? focusables[focusables.length - 1] : focusables[0];
        target.focus();

        return true;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];

    if (e.shiftKey && active === first) {
        e.preventDefault();
        last.focus();

        return true;
    }

    if (!e.shiftKey && active === last) {
        e.preventDefault();
        first.focus();

        return true;
    }

    return false;
}

/**
 * @returns {HTMLElement|null}
 */
export function captureActiveElement() {
    const el = document.activeElement;

    return el instanceof HTMLElement ? el : null;
}

/**
 * @param {HTMLElement|null|undefined} el
 */
export function restoreFocusElement(el) {
    if (el && typeof el.focus === 'function') {
        try {
            el.focus({ preventScroll: true });
        } catch {
            el.focus();
        }
    }
}
