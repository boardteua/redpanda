import assert from 'node:assert/strict';
import test from 'node:test';
import {
    classifyUrl,
    isSafeHttpUrl,
    parseChatMessageBody,
    spotifyEmbedUrl,
    trimUrlTrailing,
    youtubeVideoId,
} from './chatMessageBodyParse.js';

test('trimUrlTrailing drops trailing punctuation from URL', () => {
    assert.equal(trimUrlTrailing('https://ex.com/foo).'), 'https://ex.com/foo');
});

test('isSafeHttpUrl rejects credentials', () => {
    assert.equal(isSafeHttpUrl('https://user:pass@example.com/'), false);
    assert.equal(isSafeHttpUrl('https://example.com/'), true);
});

test('script-like text stays a single text segment', () => {
    const raw = '<script>alert(1)</script>';
    const segs = parseChatMessageBody(raw);
    assert.equal(segs.length, 1);
    assert.equal(segs[0].type, 'text');
    assert.equal(segs[0].value, raw);
});

test('plain https link segment', () => {
    const segs = parseChatMessageBody('see https://example.com/path there');
    assert.ok(segs.some((s) => s.type === 'link' && s.href === 'https://example.com/path'));
});

test('png URL becomes image segment', () => {
    const segs = parseChatMessageBody('pic https://cdn.test/x.png end');
    const img = segs.find((s) => s.type === 'image');
    assert.ok(img);
    assert.equal(img.src, 'https://cdn.test/x.png');
});

test('youtube watch URL classified as embed', () => {
    const u = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';
    const c = classifyUrl(u);
    assert.equal(c.kind, 'embed');
    assert.equal(c.provider, 'youtube');
    assert.ok(c.iframeSrc.includes('youtube-nocookie.com/embed/dQw4w9WgXcQ'));
});

test('youtubeVideoId for youtu.be', () => {
    assert.equal(youtubeVideoId('https://youtu.be/dQw4w9WgXcQ'), 'dQw4w9WgXcQ');
});

test('spotify track embed', () => {
    const u = 'https://open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh';
    assert.equal(
        spotifyEmbedUrl(u),
        'https://open.spotify.com/embed/track/4iV5W9uYEdYUVa79Axb7Rh',
    );
    const c = classifyUrl(u);
    assert.equal(c.kind, 'embed');
    assert.equal(c.provider, 'spotify');
});

test('suffix after trimmed URL preserved as text', () => {
    const segs = parseChatMessageBody('(https://ex.com/).');
    const joined = segs.map((s) => (s.type === 'text' ? s.value : s.href || s.src)).join('');
    assert.ok(joined.includes('https://ex.com'));
});
