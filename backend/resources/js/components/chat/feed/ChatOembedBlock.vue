<template>
    <div class="my-2 block max-w-full">
        <a
            v-if="phase === 'link'"
            class="break-all text-[var(--rp-link)] underline decoration-[var(--rp-link)]/40 underline-offset-2 hover:decoration-[var(--rp-link)]"
            :href="resourceUrl"
            target="_blank"
            rel="noopener noreferrer"
        >
            {{ resourceUrl }}
        </a>
        <template v-else-if="phase === 'loading'">
            <a
                class="break-all text-[var(--rp-link)] underline decoration-[var(--rp-link)]/40 underline-offset-2 hover:decoration-[var(--rp-link)]"
                :href="resourceUrl"
                target="_blank"
                rel="noopener noreferrer"
            >
                {{ resourceUrl }}
            </a>
            <span class="ml-1.5 text-xs text-[var(--rp-text-muted)]">…</span>
        </template>
        <div
            v-else-if="isLikelyVideoOembedHost"
            class="relative aspect-video w-full max-w-lg shrink-0 overflow-hidden rounded-md border border-[var(--rp-chat-chrome-border)] bg-black/5"
        >
            <iframe
                v-if="iframeSrc"
                :src="iframeSrc"
                class="absolute inset-0 box-border h-full w-full border-0"
                :title="iframeTitle"
                loading="lazy"
                :allow="iframeAllow"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
            />
        </div>
        <div
            v-else-if="phase === 'threadsRich'"
            class="threads-rich-oembed rp-chat-threads-oembed my-2 w-full max-w-[658px] overflow-hidden rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-surface-elevated)] min-h-[200px]"
            v-html="threadsRichHtml"
        />
        <div
            v-else
            class="relative w-full max-w-lg overflow-hidden rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-surface-elevated)] min-h-[200px] sm:min-h-[280px]"
        >
            <iframe
                v-if="iframeSrc"
                :src="iframeSrc"
                class="absolute inset-0 h-full w-full border-0"
                :title="iframeTitle"
                loading="lazy"
                :allow="iframeAllow"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
            />
        </div>
    </div>
</template>

<script>
import {
    ensureThreadsEmbedScript,
    fetchOembed,
    isThreadsRichOembedPayload,
    notifyThreadsEmbedsProcessed,
    parseIframeFromOembedHtml,
} from '../../../utils/oembedClient';

export default {
    name: 'ChatOembedBlock',
    props: {
        resourceUrl: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            phase: 'loading',
            iframeSrc: '',
            iframeTitle: 'Вбудований контент',
            iframeAllow:
                'autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture; web-share',
            threadsRichHtml: '',
        };
    },
    mounted() {
        this._abort = typeof AbortController !== 'undefined' ? new AbortController() : null;
        const signal = this._abort?.signal;

        fetchOembed(this.resourceUrl, signal ? { signal } : {})
            .then((data) => {
                const html = typeof data.html === 'string' ? data.html : '';
                const parsed = parseIframeFromOembedHtml(html);
                if (parsed) {
                    this.iframeSrc = parsed.src;
                    if (parsed.title) {
                        this.iframeTitle = parsed.title;
                    }
                    if (parsed.allow) {
                        this.iframeAllow = parsed.allow;
                    }
                    this.phase = 'embed';
                    return;
                }
                if (html && isThreadsRichOembedPayload(data, this.resourceUrl)) {
                    this.threadsRichHtml = html;
                    ensureThreadsEmbedScript();
                    this.phase = 'threadsRich';
                    this.$nextTick(() => {
                        notifyThreadsEmbedsProcessed();
                        window.setTimeout(() => notifyThreadsEmbedsProcessed(), 250);
                    });
                    return;
                }
                this.phase = 'link';
            })
            .catch(() => {
                this.phase = 'link';
            });
    },
    beforeDestroy() {
        this._abort?.abort();
    },
    computed: {
        /** Vimeo, Dailymotion, Twitch — типово 16×9; SoundCloud / TikTok лишаються у гілці з min-height. */
        isLikelyVideoOembedHost() {
            try {
                const h = new URL(this.resourceUrl).hostname.replace(/^www\./, '').toLowerCase();
                if (h.includes('vimeo.com') || h.includes('dailymotion.com') || h.includes('twitch.tv')) {
                    return true;
                }

                return false;
            } catch {
                return false;
            }
        },
    },
};
</script>
