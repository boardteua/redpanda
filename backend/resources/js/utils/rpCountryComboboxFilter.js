/**
 * @param {Array<{ code: string, labelUk: string }>} rows
 * @param {string} query
 * @returns {Array<{ code: string, labelUk: string }>}
 */
export function filterCountryRows(rows, query) {
    const q = (query || '').trim().toLowerCase();
    if (!q) {
        return rows;
    }

    return rows.filter((r) => {
        const code = r.code.toLowerCase();
        const label = r.labelUk.toLowerCase();

        return label.includes(q) || code.includes(q);
    });
}
