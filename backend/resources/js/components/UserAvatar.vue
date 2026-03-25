<template>
    <div
        class="rp-user-avatar relative shrink-0 overflow-hidden"
        :class="boxClass"
        :aria-hidden="decorative ? 'true' : undefined"
    >
        <img
            v-if="showImage"
            :src="src"
            :alt="decorative ? '' : altText"
            class="rp-user-avatar__img h-full w-full object-cover"
            :loading="variant === 'modal' ? 'eager' : 'lazy'"
            @error="onImgError"
        />
        <span
            v-else
            class="rp-user-avatar__fallback flex h-full w-full items-center justify-center font-bold leading-none text-white"
            :class="fallbackTextClass"
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
            validator: (v) => ['feed', 'sidebar', 'table', 'private', 'modal'].includes(v),
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
        fallbackTextClass() {
            if (this.variant === 'modal') {
                return 'text-3xl sm:text-4xl';
            }

            return 'text-[0.625rem] sm:text-xs';
        },
        boxClass() {
            if (this.variant === 'modal') {
                return 'rp-user-avatar--modal rounded-md border-2 border-[var(--rp-border-subtle)]';
            }
            if (this.variant === 'sidebar') {
                return 'rp-user-avatar--md h-9 w-9 rounded-sm border border-[var(--rp-chat-sidebar-border)]';
            }
            if (this.variant === 'table') {
                return 'rp-user-avatar--sm h-8 w-8 rounded-sm border border-[var(--rp-border-subtle)]';
            }
            if (this.variant === 'private') {
                return 'rp-user-avatar--sm h-8 w-8 shrink-0 rounded-sm border border-[var(--rp-border-subtle)]';
            }

            return 'rp-user-avatar--md h-9 w-9 rounded-sm border border-[var(--rp-chat-chrome-border)]';
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

<style scoped>
/*
 * Критичні розміри та колір гліфа поза Tailwind: якщо утиліти з цього SFC не
 * потрапляють у збірку або h-full дає 0px, плейсхолдер не виглядає «зниклим».
 */
.rp-user-avatar__fallback {
    box-sizing: border-box;
    color: #fff;
}

.rp-user-avatar.rp-user-avatar--sm,
.rp-user-avatar.rp-user-avatar--sm .rp-user-avatar__fallback {
    width: 2rem;
    min-width: 2rem;
    height: 2rem;
    min-height: 2rem;
}

.rp-user-avatar.rp-user-avatar--md,
.rp-user-avatar.rp-user-avatar--md .rp-user-avatar__fallback {
    width: 2.25rem;
    min-width: 2.25rem;
    height: 2.25rem;
    min-height: 2.25rem;
}

.rp-user-avatar.rp-user-avatar--modal,
.rp-user-avatar.rp-user-avatar--modal .rp-user-avatar__fallback {
    width: 12rem;
    min-width: 12rem;
    height: 12rem;
    min-height: 12rem;
}
</style>
