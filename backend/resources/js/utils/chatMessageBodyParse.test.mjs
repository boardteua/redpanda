import assert from 'node:assert/strict';
import test from 'node:test';
import {
    classifyUrl,
    EMBED_RESOLVERS,
    isLikelyMp4Url,
    isSafeHttpUrl,
    messageHasBlockMedia,
    isSafeEmoticonFilename,
    parseChatMessageBody,
    shouldTryBackendOembedUrl,
    spotifyEmbedUrl,
    trimUrlTrailing,
    isThreadsPostOembedUrl,
    tryFacebookPostEmbed,
    tryTelegramPostEmbed,
    tryTwitterStatusEmbed,
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

test('isLikelyMp4Url path suffix only', () => {
    assert.equal(isLikelyMp4Url('https://cdn.test/movie.mp4'), true);
    assert.equal(isLikelyMp4Url('https://cdn.test/movie.MP4?token=1'), true);
    assert.equal(isLikelyMp4Url('https://cdn.test/movie.mp4.exe'), false);
    assert.equal(isLikelyMp4Url('https://www.youtube.com/watch?v=abc'), false);
});

test('mp4 URL becomes inlineVideo segment; classifyUrl inlineVideo', () => {
    const u = 'https://cdn.test/clip.mp4';
    assert.equal(classifyUrl(u).kind, 'inlineVideo');
    const segs = parseChatMessageBody(`see ${u} here`);
    const v = segs.find((s) => s.type === 'inlineVideo');
    assert.ok(v);
    assert.equal(v.src, u);
});

test('mp4 URL with credentials stays plain text (unsafe)', () => {
    const raw = 'https://u:p@evil.test/x.mp4';
    const segs = parseChatMessageBody(raw);
    assert.ok(segs.every((s) => s.type !== 'inlineVideo'));
});

test('messageHasBlockMedia true for embed / image / oembedPending / inlineVideo', () => {
    assert.equal(messageHasBlockMedia('hi https://example.com only'), false);
    assert.equal(
        messageHasBlockMedia('v https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
        true,
    );
    assert.equal(messageHasBlockMedia('pic https://cdn.test/x.png'), true);
    assert.equal(messageHasBlockMedia('https://vimeo.com/123'), true);
    assert.equal(messageHasBlockMedia('clip https://files.test/a.Mp4?v=1 end'), true);
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

test('EMBED_RESOLVERS is ordered and has known ids', () => {
    const ids = EMBED_RESOLVERS.map((r) => r.id);
    assert.ok(ids.includes('youtube'));
    assert.ok(ids.includes('twitter'));
    assert.ok(ids.includes('telegram'));
    assert.ok(ids.includes('facebook'));
    assert.ok(ids.indexOf('youtube') < ids.indexOf('twitter'));
});

test('X / Twitter status URL → platform embed', () => {
    const u = 'https://x.com/elonmusk/status/1234567890123456789';
    const e = tryTwitterStatusEmbed(u);
    assert.ok(e);
    assert.equal(e.provider, 'twitter');
    assert.ok(e.iframeSrc.includes('platform.twitter.com/embed/Tweet.html?id=1234567890123456789'));
    assert.equal(classifyUrl(u).kind, 'embed');
});

test('Threads post permalink → oembedPending (T118, не iframe threads.net)', () => {
    const u = 'https://www.threads.net/@meta/post/AbCdEfGh12345';
    assert.equal(classifyUrl(u).kind, 'link');
    assert.equal(isThreadsPostOembedUrl(u), true);
    assert.equal(shouldTryBackendOembedUrl(u), true);
    const segs = parseChatMessageBody(`see ${u}`);
    const pending = segs.find((s) => s.type === 'oembedPending');
    assert.ok(pending);
    assert.equal(pending.href, u);
});

test('Threads /t/ short permalink → oembedPending', () => {
    const u = 'https://www.threads.com/t/DDzbnVKx57R';
    assert.equal(isThreadsPostOembedUrl(u), true);
    const segs = parseChatMessageBody(u);
    assert.ok(segs.some((s) => s.type === 'oembedPending' && s.href === u));
});

test('multiple URLs: Threads and TikTok keep distinct oembedPending hrefs', () => {
    const th = 'https://www.threads.com/@x/post/SHORT1';
    const tk = 'https://vm.tiktok.com/ZZZ123/';
    const segs = parseChatMessageBody(`a ${th} b ${tk} c`);
    const pendings = segs.filter((s) => s.type === 'oembedPending');
    assert.equal(pendings.length, 2);
    assert.ok(pendings.some((p) => p.href === th));
    assert.ok(pendings.some((p) => p.href === tk));
});

test('Telegram public post → t.me ?embed=1', () => {
    const u = 'https://t.me/telegram/42';
    const e = tryTelegramPostEmbed(u);
    assert.ok(e);
    assert.equal(e.provider, 'telegram');
    assert.equal(e.iframeSrc, 'https://t.me/telegram/42?embed=1');
});

test('Facebook story_fbid → plugins/post.php', () => {
    const u = 'https://www.facebook.com/story.php?story_fbid=999&id=1';
    const e = tryFacebookPostEmbed(u);
    assert.ok(e);
    assert.equal(e.provider, 'facebook');
    assert.ok(e.iframeSrc.includes('facebook.com/plugins/post.php?href='));
});

test('plain Facebook profile is not forced embed', () => {
    assert.equal(tryFacebookPostEmbed('https://www.facebook.com/zuck'), null);
    assert.equal(classifyUrl('https://www.facebook.com/zuck').kind, 'link');
});

test('shouldTryBackendOembedUrl for Vimeo and SoundCloud', () => {
    assert.equal(shouldTryBackendOembedUrl('https://vimeo.com/123456789'), true);
    assert.equal(shouldTryBackendOembedUrl('https://player.vimeo.com/video/1'), true);
    assert.equal(shouldTryBackendOembedUrl('https://soundcloud.com/artist/track'), true);
    assert.equal(shouldTryBackendOembedUrl('https://www.twitch.tv/name'), true);
    assert.equal(shouldTryBackendOembedUrl('https://vm.tiktok.com/AbC'), true);
    assert.equal(shouldTryBackendOembedUrl('https://example.com/x'), false);
    assert.equal(shouldTryBackendOembedUrl('https://www.youtube.com/watch?v=abc'), false);
});

test('Vimeo watch URL becomes oembedPending segment', () => {
    const u = 'https://vimeo.com/123456789';
    assert.equal(classifyUrl(u).kind, 'link');
    const segs = parseChatMessageBody(`check ${u} out`);
    const pending = segs.find((s) => s.type === 'oembedPending');
    assert.ok(pending);
    assert.equal(pending.href, u);
});

test('isSafeEmoticonFilename rejects traversal', () => {
    assert.equal(isSafeEmoticonFilename('ok.gif'), true);
    assert.equal(isSafeEmoticonFilename('../x.gif'), false);
    assert.equal(isSafeEmoticonFilename('a/b.gif'), false);
});

test('known :code: becomes emoticon segment (T63)', () => {
    const idx = { hi: 'hi.gif' };
    const segs = parseChatMessageBody('Привіт :hi: всім', { emoticonIndex: idx });
    const emo = segs.find((s) => s.type === 'emoticon');
    assert.ok(emo);
    assert.equal(emo.code, 'hi');
    assert.equal(emo.src, '/emoticon/hi.gif');
});

test('unknown :code: stays plain text', () => {
    const segs = parseChatMessageBody(':unknown:', { emoticonIndex: { hi: 'hi.gif' } });
    assert.ok(segs.every((s) => s.type === 'text'));
    assert.equal(segs.map((s) => s.value).join(''), ':unknown:');
});

test('emoticon coexists with URL in same message', () => {
    const segs = parseChatMessageBody(':x: https://example.com/y.png', {
        emoticonIndex: { x: 'x.gif' },
    });
    assert.ok(segs.some((s) => s.type === 'emoticon'));
    assert.ok(segs.some((s) => s.type === 'image'));
});
