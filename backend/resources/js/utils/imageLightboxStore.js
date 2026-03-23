import Vue from 'vue';
import { restoreFocusElement } from './modalFocusTrap';

export const imageLightboxStore = Vue.observable({
    open: false,
    src: '',
    alt: '',
    /** @type {HTMLElement|null} */
    returnFocusEl: null,
});

/**
 * @param {{ src: string, alt?: string, returnFocusEl?: HTMLElement|null }} opts
 */
export function openImageLightbox({ src, alt = '', returnFocusEl = null }) {
    if (!src) {
        return;
    }
    imageLightboxStore.src = src;
    imageLightboxStore.alt = alt || 'Зображення у повідомленні';
    imageLightboxStore.returnFocusEl = returnFocusEl instanceof HTMLElement ? returnFocusEl : null;
    imageLightboxStore.open = true;
}

export function closeImageLightbox() {
    const el = imageLightboxStore.returnFocusEl;
    imageLightboxStore.open = false;
    imageLightboxStore.src = '';
    imageLightboxStore.alt = '';
    imageLightboxStore.returnFocusEl = null;
    Vue.nextTick(() => {
        restoreFocusElement(el);
    });
}
