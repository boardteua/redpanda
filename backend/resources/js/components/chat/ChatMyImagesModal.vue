<template>
    <RpModal
        :open="open"
        :z-index="78"
        variant="framed"
        size="lg"
        max-height-class="max-h-[min(85vh,40rem)]"
        :aria-labelledby="titleId"
        :scroll-body="false"
        :aria-busy="loadingMore"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-start justify-between gap-2 border-b border-[var(--rp-border-subtle)] p-4">
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">
                    Останні додані картинки
                </h2>
                <button type="button" class="rp-focusable rp-btn rp-btn-ghost text-sm" @click="close">
                    Закрити
                </button>
            </div>
        </template>
        <div class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <div class="min-h-0 flex-1 overflow-y-auto p-4">
                <p v-if="loading && items.length === 0" class="text-sm text-[var(--rp-text-muted)]">
                    Завантаження…
                </p>
                <p
                    v-else-if="error"
                    class="text-sm text-[var(--rp-error)]"
                    role="alert"
                >
                    {{ error }}
                </p>
                <p
                    v-else-if="!loading && items.length === 0"
                    class="text-sm text-[var(--rp-text-muted)]"
                >
                    Ще немає зображень. Додайте фото кнопкою з іконкою знімка біля поля вводу.
                </p>
                <div
                    v-else
                    class="grid grid-cols-3 gap-2 sm:grid-cols-4"
                    role="list"
                >
                    <button
                        v-for="it in items"
                        :key="it.id"
                        type="button"
                        class="rp-focusable group relative aspect-square overflow-hidden rounded-md border border-[var(--rp-chat-chrome-border)] bg-[var(--rp-chat-row-even)]"
                        :aria-label="'Вставити зображення: ' + (it.file_name || it.id)"
                        role="listitem"
                        @click="pick(it)"
                    >
                        <img
                            :src="it.url"
                            :alt="''"
                            class="h-full w-full object-cover transition-opacity group-hover:opacity-90"
                            loading="lazy"
                        />
                    </button>
                </div>
                <div v-if="items.length > 0 && currentPage < lastPage" class="mt-4 flex justify-center">
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-ghost text-sm"
                        :disabled="loadingMore"
                        @click="loadMore"
                    >
                        {{ loadingMore ? 'Завантаження…' : 'Завантажити ще' }}
                    </button>
                </div>
            </div>
        </div>
    </RpModal>
</template>

<script>
import RpModal from '../RpModal.vue';

let modalSeq = 0;

export default {
    name: 'ChatMyImagesModal',
    components: { RpModal },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        ensureSanctum: {
            type: Function,
            required: true,
        },
    },
    data() {
        modalSeq += 1;

        return {
            titleId: `chat-my-images-title-${modalSeq}`,
            items: [],
            loading: false,
            loadingMore: false,
            error: '',
            currentPage: 1,
            lastPage: 1,
        };
    },
    watch: {
        open(v) {
            if (v) {
                this.resetAndLoad();
            }
        },
    },
    methods: {
        close() {
            this.$emit('close');
        },
        resetAndLoad() {
            this.items = [];
            this.error = '';
            this.currentPage = 1;
            this.lastPage = 1;
            this.fetchPage(1, false);
        },
        async fetchPage(page, append) {
            if (append) {
                this.loadingMore = true;
            } else {
                this.loading = true;
            }
            this.error = '';
            await this.ensureSanctum();
            try {
                const { data } = await window.axios.get('/api/v1/images', {
                    params: { per_page: 24, page },
                });
                const chunk = Array.isArray(data.data) ? data.data : [];
                this.items = append ? this.items.concat(chunk) : chunk;
                this.currentPage = data.current_page ?? page;
                this.lastPage = data.last_page ?? 1;
            } catch (err) {
                const msg = err.response?.data?.message || 'Не вдалося завантажити список зображень.';
                this.error = msg;
                if (!append) {
                    this.items = [];
                }
            } finally {
                this.loading = false;
                this.loadingMore = false;
            }
        },
        loadMore() {
            if (this.currentPage >= this.lastPage || this.loadingMore) {
                return;
            }
            this.fetchPage(this.currentPage + 1, true);
        },
        pick(it) {
            if (!it || it.id == null || !it.url) {
                return;
            }
            this.$emit('select', { id: it.id, url: it.url });
            this.close();
        },
    },
};
</script>
