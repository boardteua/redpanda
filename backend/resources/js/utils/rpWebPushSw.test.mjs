import assert from 'node:assert/strict';
import test from 'node:test';
import {
    RP_PUSH_SW_PATH,
    workerScriptIsOurPushSw,
} from './rpWebPushSw.js';

const origin = 'http://127.0.0.1:8080';

test('matches exact /build/sw.js', () => {
    assert.equal(
        workerScriptIsOurPushSw(`${origin}${RP_PUSH_SW_PATH}`, origin),
        true,
    );
    assert.equal(workerScriptIsOurPushSw(`${origin}${RP_PUSH_SW_PATH}?v=1`, origin), true);
});

test('rejects other paths that merely contain sw.js', () => {
    assert.equal(workerScriptIsOurPushSw(`${origin}/build/workbox-sw.js`, origin), false);
    assert.equal(workerScriptIsOurPushSw(`${origin}/vendor/foo-sw.js`, origin), false);
});

test('rejects empty or invalid', () => {
    assert.equal(workerScriptIsOurPushSw('', origin), false);
    assert.equal(workerScriptIsOurPushSw(undefined, origin), false);
    assert.equal(workerScriptIsOurPushSw('not a url', origin), false);
});
