import test from 'node:test';
import assert from 'node:assert/strict';
import {
    PRESENCE_STATUS_UNKNOWN,
    normalizedPresenceStatus,
    presenceRowClass,
    presenceDotClass,
    presenceLabelUa,
} from './chatSidebarPresence.js';

test('normalizedPresenceStatus maps known API strings', () => {
    assert.equal(normalizedPresenceStatus('online'), 'online');
    assert.equal(normalizedPresenceStatus('away'), 'away');
    assert.equal(normalizedPresenceStatus('inactive'), 'inactive');
    assert.equal(normalizedPresenceStatus(undefined), 'online');
    assert.equal(normalizedPresenceStatus(null), 'online');
});

test('normalizedPresenceStatus preserves unknown sentinel', () => {
    assert.equal(normalizedPresenceStatus(PRESENCE_STATUS_UNKNOWN), PRESENCE_STATUS_UNKNOWN);
});

test('unknown shares inactive row/dot classes (T126)', () => {
    assert.equal(presenceRowClass(PRESENCE_STATUS_UNKNOWN), presenceRowClass('inactive'));
    assert.equal(presenceDotClass(PRESENCE_STATUS_UNKNOWN), presenceDotClass('inactive'));
});

test('presenceLabelUa distinguishes unknown from inactive', () => {
    assert.match(presenceLabelUa(PRESENCE_STATUS_UNKNOWN), /уточнюється/);
    assert.match(presenceLabelUa('inactive'), /Неактивний/);
});
