import { setChatEmoticonIndex } from './chatMessageBodyParse';

/**
 * Завантажити активний каталог смайлів з API і оновити індекс для парсера повідомлень (T63).
 *
 * @returns {Promise<Array<{ code: string, display_name: string, file: string, keywords: string }>>}
 */
export async function loadChatEmoticonsCatalog() {
    try {
        const { data } = await window.axios.get('/api/v1/chat/emoticons');
        const rows = Array.isArray(data.data) ? data.data : [];
        const index = {};
        rows.forEach((row) => {
            if (row && row.code && row.file) {
                index[String(row.code).toLowerCase()] = String(row.file);
            }
        });
        setChatEmoticonIndex(index);

        return rows;
    } catch {
        setChatEmoticonIndex(null);

        return [];
    }
}
