<template>
    <RpModal
        :open="open"
        variant="framed"
        size="lg"
        max-height-class="max-h-[min(90vh,36rem)]"
        :aria-labelledby="titleId"
        :scroll-body="true"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3">
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">Налаштування чату</h2>
                <button
                    type="button"
                    class="rp-focusable flex h-11 w-11 shrink-0 items-center justify-center rounded-md text-[var(--rp-text-muted)] hover:bg-[var(--rp-surface-elevated)]"
                    aria-label="Закрити"
                    @click="close"
                >
                    <svg class="h-6 w-6" aria-hidden="true" viewBox="0 0 24 24" fill="currentColor">
                        <path
                            d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
                        />
                    </svg>
                </button>
            </div>
        </template>

        <div class="space-y-4 px-4 py-4">
            <p
                v-if="loadError"
                role="alert"
                class="rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
            >
                {{ loadError }}
            </p>
            <p
                v-if="saveError"
                role="alert"
                class="rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
            >
                {{ saveError }}
            </p>

            <fieldset :disabled="loading || saving" class="space-y-4">
                <legend class="sr-only">Параметри створення кімнат (T51)</legend>

                <div>
                    <label class="rp-label" for="cs-n">Поріг N (мінімум публічних повідомлень для права створити кімнату)</label>
                    <input
                        id="cs-n"
                        v-model.number="form.room_create_min_public_messages"
                        type="number"
                        min="0"
                        max="99999999"
                        class="rp-input rp-focusable w-full max-w-xs"
                    />
                    <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                        Ціле ≥ 0. Звичайний зареєстрований отримує право лише якщо його лічильник <strong>строго більший</strong> за N
                        (лічба в бекенді — окремий таск T44).
                    </p>
                </div>

                <div>
                    <label class="rp-label" for="cs-scope">Область лічби публічних повідомлень</label>
                    <select id="cs-scope" v-model="form.public_message_count_scope" class="rp-input rp-focusable w-full max-w-xl">
                        <option value="all_public_rooms">Усі публічні кімнати (усього чату)</option>
                        <option value="default_room_only">Лише в одній обраній кімнаті</option>
                    </select>
                    <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                        Приватні повідомлення не враховуються. Для однієї кімнати оберіть публічну кімнату нижче.
                    </p>
                </div>

                <div v-if="form.public_message_count_scope === 'default_room_only'">
                    <label class="rp-label" for="cs-room">Кімната для лічби</label>
                    <select id="cs-room" v-model.number="roomSelect" class="rp-input rp-focusable w-full max-w-xl">
                        <option :value="0">— не обрано (бекенд використає дефолтну публічну) —</option>
                        <option v-for="r in publicRooms" :key="r.room_id" :value="r.room_id">
                            {{ r.room_name }} (#{{ r.room_id }})
                        </option>
                    </select>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-primary text-sm"
                        :disabled="saving || loading"
                        @click="save"
                    >
                        {{ saving ? 'Збереження…' : 'Зберегти' }}
                    </button>
                </div>
            </fieldset>
        </div>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';

let titleSeq = 0;

export default {
    name: 'ChatSettingsModal',
    components: { RpModal },
    props: {
        open: { type: Boolean, default: false },
        rooms: { type: Array, default: () => [] },
        ensureSanctum: { type: Function, required: true },
    },
    data() {
        titleSeq += 1;

        return {
            titleId: `chat-settings-title-${titleSeq}`,
            loading: false,
            saving: false,
            loadError: '',
            saveError: '',
            form: {
                room_create_min_public_messages: 100,
                public_message_count_scope: 'all_public_rooms',
                message_count_room_id: null,
            },
        };
    },
    computed: {
        publicRooms() {
            const list = this.rooms || [];

            return list.filter((r) => Number(r.access) === 0);
        },
        roomSelect: {
            get() {
                const id = this.form.message_count_room_id;

                return id != null && id !== '' ? Number(id) : 0;
            },
            set(v) {
                const n = Number(v);

                this.form.message_count_room_id = n > 0 ? n : null;
            },
        },
    },
    watch: {
        open(v) {
            if (v) {
                this.loadError = '';
                this.saveError = '';
                this.load();
            }
        },
    },
    methods: {
        close() {
            this.$emit('close');
        },
        async load() {
            this.loading = true;
            this.loadError = '';
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.get('/api/v1/chat/settings');
                const d = data && data.data;
                if (!d) {
                    this.loadError = 'Порожня відповідь сервера.';

                    return;
                }
                this.form = {
                    room_create_min_public_messages: Number(d.room_create_min_public_messages) || 0,
                    public_message_count_scope:
                        d.public_message_count_scope === 'default_room_only'
                            ? 'default_room_only'
                            : 'all_public_rooms',
                    message_count_room_id: d.message_count_room_id != null ? Number(d.message_count_room_id) : null,
                };
            } catch {
                this.loadError = 'Не вдалося завантажити налаштування.';
            } finally {
                this.loading = false;
            }
        },
        async save() {
            this.saving = true;
            this.saveError = '';
            try {
                await this.ensureSanctum();
                const body = {
                    room_create_min_public_messages: this.form.room_create_min_public_messages,
                    public_message_count_scope: this.form.public_message_count_scope,
                };
                if (this.form.public_message_count_scope === 'default_room_only') {
                    body.message_count_room_id = this.form.message_count_room_id;
                } else {
                    body.message_count_room_id = null;
                }
                await window.axios.patch('/api/v1/chat/settings', body);
                this.$emit('saved');
                this.close();
            } catch (e) {
                const st = e.response && e.response.status;
                if (st === 403) {
                    this.saveError = 'Недостатньо прав (потрібен адміністратор чату).';
                } else {
                    this.saveError =
                        (e.response && e.response.data && e.response.data.message) || 'Не вдалося зберегти.';
                }
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
