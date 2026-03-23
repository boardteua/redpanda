<template>
    <RpModal
        :open="open"
        :z-index="78"
        variant="framed"
        size="md"
        max-height-class="max-h-[min(85vh,32rem)]"
        :aria-labelledby="titleId"
        :scroll-body="false"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-start justify-between gap-2 border-b border-[var(--rp-border-subtle)] p-4">
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                    Смайли
                </h2>
                <button type="button" class="rp-focusable rp-btn rp-btn-ghost text-sm" @click="close">
                    Закрити
                </button>
            </div>
        </template>
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <div class="shrink-0 border-b border-[var(--rp-border-subtle)] p-4">
                <label class="rp-label" :for="searchId">Шукати смайли</label>
                <input
                    :id="searchId"
                    ref="searchInput"
                    v-model.trim="searchQuery"
                    data-rp-initial-focus
                    type="search"
                    autocomplete="off"
                    class="rp-input rp-focusable mt-1 w-full"
                    placeholder="Код або назва…"
                    maxlength="80"
                    :disabled="catalogLoading"
                    :aria-describedby="usageHintId"
                />
                <p :id="usageHintId" class="mt-1 text-xs text-[var(--rp-text-muted)]">
                    Порядок: спочатку ті, що ви обирали частіше (лише на цьому пристрої).
                </p>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <p v-if="catalogLoading" class="text-sm text-[var(--rp-text-muted)]" role="status">
                    Завантаження каталогу…
                </p>
                <p v-else-if="catalogLoadError" role="alert" class="text-sm text-[var(--rp-error)]">
                    {{ catalogLoadError }}
                </p>
                <p v-else-if="catalogItems.length === 0" class="text-sm text-[var(--rp-text-muted)]" role="status">
                    Каталог порожній. Адміністратор може додати смайли в «Налаштування чату».
                </p>
                <p v-else-if="filtered.length === 0" class="text-sm text-[var(--rp-text-muted)]" role="status">
                    Нічого не знайдено. Спробуйте інший запит.
                </p>
                <div
                    v-else
                    class="grid grid-cols-4 gap-2 sm:grid-cols-5"
                    role="list"
                >
                    <button
                        v-for="it in filtered"
                        :key="it.code"
                        type="button"
                        class="rp-focusable flex flex-col items-center gap-1 rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-row-even)] px-1 py-2 text-center"
                        role="listitem"
                        :title="it.display_name || ':' + it.code + ':'"
                        :aria-label="'Вставити смайл ' + ':' + it.code + ':'"
                        @click="pick(it)"
                    >
                        <img
                            v-if="it.file"
                            :src="'/emoticon/' + it.file"
                            :alt="it.display_name || it.code"
                            class="h-8 w-8 object-contain"
                            loading="lazy"
                            decoding="async"
                        />
                        <span
                            v-else
                            class="flex h-8 w-8 items-center justify-center text-2xl leading-none"
                            aria-hidden="true"
                        >?</span>
                        <span class="max-w-full truncate font-mono text-[0.65rem] text-[var(--rp-text-muted)]">
                            :{{ it.code }}:
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </RpModal>
</template>

<script>
import RpModal from '../RpModal.vue';
import { filterEmojiItems } from '../../utils/chatEmojiCatalog';
import { loadChatEmoticonsCatalog } from '../../utils/chatEmoticons';
import { getEmoticonUsageCounts, recordEmoticonUsage } from '../../utils/chatEmoticonUsage';

let modalSeq = 0;

export default {
    name: 'ChatEmojiModal',
    components: { RpModal },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
    },
    data() {
        modalSeq += 1;

        return {
            titleId: `chat-emoji-title-${modalSeq}`,
            searchId: `chat-emoji-search-${modalSeq}`,
            usageHintId: `chat-emoji-usage-hint-${modalSeq}`,
            searchQuery: '',
            catalogItems: [],
            catalogLoading: false,
            catalogLoadError: '',
        };
    },
    computed: {
        filtered() {
            const items = filterEmojiItems(this.catalogItems, this.searchQuery);
            const usage = getEmoticonUsageCounts();

            return [...items].sort((a, b) => {
                const ca = String(a.code || '').toLowerCase();
                const cb = String(b.code || '').toLowerCase();
                const ua = usage[ca] || 0;
                const ub = usage[cb] || 0;
                if (ub !== ua) {
                    return ub - ua;
                }

                return ca.localeCompare(cb, 'uk', { sensitivity: 'base' });
            });
        },
    },
    watch: {
        open(v) {
            if (v) {
                this.searchQuery = '';
                this.refreshCatalog();
            }
        },
    },
    methods: {
        close() {
            this.$emit('close');
        },
        async refreshCatalog() {
            this.catalogLoading = true;
            this.catalogLoadError = '';
            try {
                this.catalogItems = await loadChatEmoticonsCatalog();
            } catch {
                this.catalogLoadError = 'Не вдалося завантажити каталог смайлів.';
                this.catalogItems = [];
            } finally {
                this.catalogLoading = false;
            }
        },
        pick(it) {
            if (!it || !it.code) {
                return;
            }
            recordEmoticonUsage(it.code);
            this.$emit('select', { code: it.code });
            this.close();
        },
    },
};
</script>
