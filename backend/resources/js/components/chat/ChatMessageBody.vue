<template>
    <div :class="rootClass">
        <template v-for="(seg, i) in segments">
            <span v-if="seg.type === 'text'" :key="'t-' + i">{{ seg.value }}</span>
            <a
                v-else-if="seg.type === 'link'"
                :key="'a-' + i"
                class="break-all text-[var(--rp-link)] underline decoration-[var(--rp-link)]/40 underline-offset-2 hover:decoration-[var(--rp-link)]"
                :href="seg.href"
                target="_blank"
                rel="noopener noreferrer"
            >
                {{ seg.label }}
            </a>
            <a
                v-else-if="seg.type === 'embed' && variant === 'archive'"
                :key="'ae-' + i"
                class="break-all text-[var(--rp-link)] underline decoration-[var(--rp-link)]/40 underline-offset-2 hover:decoration-[var(--rp-link)]"
                :href="seg.src"
                target="_blank"
                rel="noopener noreferrer"
            >
                {{ embedArchiveLabel(seg) }}
            </a>
            <figure
                v-else-if="seg.type === 'image' && variant !== 'archive'"
                :key="'img-' + i"
                class="my-1.5 block max-w-full"
            >
                <img
                    :src="seg.src"
                    :alt="seg.alt"
                    class="max-h-48 max-w-full rounded-md border border-[var(--rp-chat-chrome-border)] object-contain"
                    loading="lazy"
                    referrerpolicy="no-referrer"
                />
            </figure>
            <img
                v-else-if="seg.type === 'image' && variant === 'archive'"
                :key="'imga-' + i"
                :src="seg.src"
                alt=""
                class="mb-2 max-h-20 max-w-full rounded border border-[var(--rp-border-subtle)] object-contain"
                loading="lazy"
                referrerpolicy="no-referrer"
            />
            <div v-else-if="seg.type === 'embed' && variant !== 'archive'" :key="'em-' + i" class="my-2 block max-w-full">
                <div
                    v-if="seg.provider === 'youtube'"
                    class="relative aspect-video w-full max-w-lg overflow-hidden rounded-md border border-[var(--rp-chat-chrome-border)] bg-black/5"
                >
                    <iframe
                        :src="seg.src"
                        class="absolute inset-0 h-full w-full"
                        :title="embedTitle(seg)"
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allowfullscreen
                    />
                </div>
                <div
                    v-else-if="seg.provider === 'spotify'"
                    class="w-full max-w-lg overflow-hidden rounded-md border border-[var(--rp-chat-chrome-border)]"
                >
                    <iframe
                        :src="seg.src"
                        class="h-[152px] w-full"
                        :title="embedTitle(seg)"
                        loading="lazy"
                        allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"
                        referrerpolicy="strict-origin-when-cross-origin"
                    />
                </div>
                <div
                    v-else-if="seg.provider === 'apple'"
                    class="w-full max-w-lg overflow-hidden rounded-md border border-[var(--rp-chat-chrome-border)]"
                >
                    <iframe
                        :src="seg.src"
                        class="h-[min(280px,45vh)] w-full sm:h-[380px]"
                        :title="embedTitle(seg)"
                        loading="lazy"
                        allow="autoplay *; encrypted-media *; fullscreen *; clipboard-write"
                        referrerpolicy="strict-origin-when-cross-origin"
                    />
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import { parseChatMessageBody } from '../../utils/chatMessageBodyParse';

export default {
    name: 'ChatMessageBody',
    props: {
        /** Plain text з API (post_message / body). */
        text: {
            type: String,
            default: '',
        },
        /** feed — повний рендер; archive — ембеди як посилання, компактні картинки; private — як feed. */
        variant: {
            type: String,
            default: 'feed',
            validator(v) {
                return ['feed', 'archive', 'private'].includes(v);
            },
        },
        /** Додаткові класи для кореня (у т.ч. стилі T30). */
        bodyClass: {
            type: [String, Array, Object],
            default: '',
        },
    },
    computed: {
        segments() {
            return parseChatMessageBody(this.text);
        },
        rootClass() {
            const base = ['whitespace-pre-wrap', 'break-words'];
            const bc = this.bodyClass;
            if (!bc) {
                return base;
            }
            if (Array.isArray(bc)) {
                return base.concat(bc);
            }
            if (typeof bc === 'string') {
                return base.concat([bc]);
            }
            return [...base, bc];
        },
    },
    methods: {
        embedTitle(seg) {
            if (seg.provider === 'youtube') {
                return 'Вбудоване відео YouTube';
            }
            if (seg.provider === 'spotify') {
                return 'Вбудований плеєр Spotify';
            }
            if (seg.provider === 'apple') {
                return 'Вбудований плеєр Apple Music';
            }
            return 'Вбудований медіаплеєр';
        },
        embedArchiveLabel(seg) {
            if (seg.provider === 'youtube') {
                return 'Відео YouTube (відкрити)';
            }
            if (seg.provider === 'spotify') {
                return 'Spotify (відкрити)';
            }
            if (seg.provider === 'apple') {
                return 'Apple Music (відкрити)';
            }
            return 'Медіа (відкрити)';
        },
    },
};
</script>
