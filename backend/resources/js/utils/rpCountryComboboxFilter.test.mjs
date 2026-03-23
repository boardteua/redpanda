import assert from 'node:assert/strict';
import test from 'node:test';
import { filterCountryRows } from './rpCountryComboboxFilter.js';

const rows = [
    { code: 'UA', labelUk: 'Україна' },
    { code: 'PL', labelUk: 'Польща' },
    { code: 'DE', labelUk: 'Німеччина' },
];

test('empty query returns all rows', () => {
    assert.deepEqual(filterCountryRows(rows, ''), rows);
    assert.deepEqual(filterCountryRows(rows, '   '), rows);
});

test('filters by Ukrainian label substring', () => {
    const r = filterCountryRows(rows, 'поль');
    assert.equal(r.length, 1);
    assert.equal(r[0].code, 'PL');
});

test('filters by code case-insensitively', () => {
    const r = filterCountryRows(rows, 'ua');
    assert.equal(r.length, 1);
    assert.equal(r[0].code, 'UA');
});

test('no match returns empty array', () => {
    assert.deepEqual(filterCountryRows(rows, 'zzz'), []);
});
