/**
 * Демо-набір смайлів для модалу композера (T33).
 * Після підключення пакета GIF/SVG з борду — замінити або злити з manifest (див. docs/chat-v2/T33-EMOJI-ASSETS.md).
 *
 * @typedef {{ code: string, title: string, glyph: string, search: string }} ChatEmojiItem
 */

/** @type {ChatEmojiItem[]} */
export const CHAT_EMOJI_CATALOG = [
    { code: 'hi', title: 'Привіт', glyph: '👋', search: 'hi привіт hello' },
    { code: 'smile', title: 'Посмішка', glyph: '😊', search: 'smile усмішка' },
    { code: 'lol', title: 'Сміх', glyph: '😂', search: 'lol сміх' },
    { code: 'wink', title: 'Підморгування', glyph: '😉', search: 'wink' },
    { code: 'sad', title: 'Сумно', glyph: '😢', search: 'sad сум' },
    { code: 'heart', title: 'Серце', glyph: '❤️', search: 'heart любов' },
    { code: 'thumbsup', title: 'Клас', glyph: '👍', search: 'thumbs ок так' },
    { code: 'thumbsdown', title: 'Не так', glyph: '👎', search: 'thumbs ні' },
    { code: 'fire', title: 'Вогонь', glyph: '🔥', search: 'fire гаряче' },
    { code: 'bike', title: 'Велосипед', glyph: '🚴', search: 'bike вело velo' },
    { code: 'flag_ua', title: 'Прапор України', glyph: '🇺🇦', search: 'ua україна flag' },
    { code: 'coffee', title: 'Кава', glyph: '☕', search: 'coffee кава' },
];

/**
 * @param {string} q
 * @returns {ChatEmojiItem[]}
 */
export function filterEmojiCatalog(q) {
    const s = (q || '').trim().toLowerCase();
    if (!s) {
        return [...CHAT_EMOJI_CATALOG];
    }

    return CHAT_EMOJI_CATALOG.filter((it) => {
        if (it.code.toLowerCase().includes(s)) {
            return true;
        }
        if (it.title.toLowerCase().includes(s)) {
            return true;
        }

        return it.search.toLowerCase().split(/\s+/).some((w) => w.includes(s) || s.includes(w));
    });
}
