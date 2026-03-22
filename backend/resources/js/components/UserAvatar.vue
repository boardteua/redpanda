<template>
    <div
        class="relative shrink-0 overflow-hidden"
        :class="boxClass"
        :aria-hidden="decorative ? 'true' : undefined"
    >
        <img
            v-if="showImage"
            :src="src"
            :alt="decorative ? '' : altText"
            class="h-full w-full object-cover"
            loading="lazy"
            @error="onImgError"
        />
        <span
            v-else
            class="flex h-full w-full items-center justify-center text-[0.625rem] font-bold leading-none text-white sm:text-xs"
            :style="{ backgroundColor: avatarBackgroundFromName(displayName) }"
        >
            {{ initials }}
        </span>
    </div>
</template>

<script>
import { avatarBackgroundFromName, initialsFromDisplayName } from '../lib/avatarUtils';

export default {
    name: 'UserAvatar',
    props: {
        /** Абсолютний або відносний URL зображення; порожній/null — ініціали. */
        src: {
            type: String,
            default: '',
        },
        /** Відображуване ім’я (нік) для ініціалів і alt. */
        name: {
            type: String,
            default: '',
        },
        /** Варіант рамки/розміру під контекст чату. */
        variant: {
            type: String,
            default: 'feed',
            validator: (v) => ['feed', 'sidebar', 'table', 'private'].includes(v),
        },
        /** Якщо true — приховує зображення від assistive tech (як декоративний плейсхолдер у стрічці). */
        decorative: {
            type: Boolean,
            default: true,
        },
    },
    data() {
        return {
            imageFailed: false,
        };
    },
    computed: {
        displayName() {
            return this.name || '';
        },
        initials() {
            return initialsFromDisplayName(this.displayName);
        },
        trimmedSrc() {
            return typeof this.src === 'string' ? this.src.trim() : '';
        },
        showImage() {
            return Boolean(this.trimmedSrc) && !this.imageFailed;
        },
        altText() {
            return this.displayName ? `Аватар: ${this.displayName}` : 'Аватар';
        },
        boxClass() {
            if (this.variant === 'sidebar') {
                return 'h-9 w-9 rounded-sm border border-[var(--rp-chat-sidebar-border)]';
            }
            if (this.variant === 'table') {
                return 'h-8 w-8 rounded-sm border border-[var(--rp-border-subtle)]';
            }
            if (this.variant === 'private') {
                return 'h-8 w-8 shrink-0 rounded-sm border border-[var(--rp-border-subtle)]';
            }

            return 'h-9 w-9 rounded-sm border border-[var(--rp-chat-chrome-border)]';
        },
    },
    watch: {
        src() {
            this.imageFailed = false;
        },
    },
    methods: {
        avatarBackgroundFromName,
        onImgError() {
            this.imageFailed = true;
        },
    },
};
</script>
