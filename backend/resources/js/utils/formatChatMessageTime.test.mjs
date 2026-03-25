import test from 'node:test';
import assert from 'node:assert/strict';
import {
    dateFromUnixSeconds,
    formatChatMessageTimeLocal,
    formatChatArchiveDateTimeLocal,
    isoUtcFromUnixSeconds,
} from './formatChatMessageTime.js';

test('dateFromUnixSeconds rejects invalid', () => {
    assert.equal(dateFromUnixSeconds(null), null);
    assert.equal(dateFromUnixSeconds(''), null);
    assert.equal(dateFromUnixSeconds(NaN), null);
    assert.equal(dateFromUnixSeconds(-1), null);
    assert.equal(dateFromUnixSeconds(0), null);
});

test('dateFromUnixSeconds accepts positive int', () => {
    const d = dateFromUnixSeconds(1700000000);
    assert.ok(d instanceof Date);
    assert.equal(Number.isNaN(d.getTime()), false);
});

test('formatChatMessageTimeLocal returns non-empty for valid unix', () => {
    const s = formatChatMessageTimeLocal(1700000000);
    assert.equal(typeof s, 'string');
    assert.ok(s.length > 0);
});

test('formatChatArchiveDateTimeLocal returns non-empty for valid unix', () => {
    const s = formatChatArchiveDateTimeLocal(1700000000);
    assert.equal(typeof s, 'string');
    assert.ok(s.length > 0);
});

test('isoUtcFromUnixSeconds is ISO Z for known instant', () => {
    assert.equal(isoUtcFromUnixSeconds(0), '');
    assert.match(isoUtcFromUnixSeconds(1700000000), /^\d{4}-\d{2}-\d{2}T/);
});
