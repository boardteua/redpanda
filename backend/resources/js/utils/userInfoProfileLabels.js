import countryRows from '../../data/iso3166-alpha2-uk.json';
import { normalizeStoredCountryCode } from './countryProfile.js';

const VALID_COUNTRY_CODES = new Set(countryRows.map((r) => r.code));

/**
 * @param {unknown} code
 * @returns {string|null}
 */
export function countryLabelUk(code) {
    if (code == null || code === '') {
        return null;
    }
    const c = normalizeStoredCountryCode(code, VALID_COUNTRY_CODES);
    if (!c) {
        return null;
    }
    const row = countryRows.find((r) => r.code === c);

    return row ? row.labelUk : null;
}

/**
 * @param {unknown} sex
 * @returns {string|null}
 */
export function sexLabelUk(sex) {
    if (sex == null || sex === '') {
        return null;
    }
    const map = {
        male: 'Чоловік',
        female: 'Жінка',
        other: 'Інше',
        prefer_not: 'Не вказувати',
    };

    return map[sex] || null;
}
