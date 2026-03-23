/**
 * @param {unknown} raw
 * @param {Set<string>} validCodes uppercase ISO2
 * @returns {string} code or '' if unknown / legacy free text
 */
export function normalizeStoredCountryCode(raw, validCodes) {
    if (raw == null || raw === '') {
        return '';
    }
    const s = String(raw).trim().toUpperCase();
    if (s.length === 2 && validCodes.has(s)) {
        return s;
    }

    return '';
}
