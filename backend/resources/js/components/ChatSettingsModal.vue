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

            <div class="border-t border-[var(--rp-border-subtle)] pt-4">
                <h3 class="text-sm font-semibold text-[var(--rp-text)]">Каталог смайлів</h3>
                <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                    Файли відображаються за шляхом <span class="font-mono">/emoticon/</span> (GIF, PNG, WebP до 512&nbsp;КБ).
                    У тексті використовуйте <span class="font-mono">:код:</span>.
                </p>
                <p
                    v-if="emoticonError"
                    role="alert"
                    class="mt-2 rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
                >
                    {{ emoticonError }}
                </p>
                <p v-if="emoticonLoading" class="mt-2 text-sm text-[var(--rp-text-muted)]" role="status">
                    Завантаження списку…
                </p>
                <div v-else class="mt-3 overflow-x-auto rounded-md border border-[var(--rp-border-subtle)]">
                    <table class="w-full min-w-[20rem] border-collapse text-left text-sm text-[var(--rp-text)]">
                        <thead class="bg-[var(--rp-surface-elevated)] text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                            <tr>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-2 py-2">Прев’ю</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-2 py-2">Код</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-2 py-2">Назва</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-2 py-2">Активний</th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-2 py-2">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in emoticonList" :key="e.id">
                                <td class="border-b border-[var(--rp-border-subtle)] px-2 py-1">
                                    <img
                                        v-if="e.file"
                                        :src="'/emoticon/' + e.file"
                                        alt=""
                                        class="h-8 w-8 object-contain"
                                        loading="lazy"
                                    />
                                </td>
                                <td class="border-b border-[var(--rp-border-subtle)] px-2 py-1 font-mono text-xs">
                                    :{{ e.code }}:
                                </td>
                                <td class="border-b border-[var(--rp-border-subtle)] px-2 py-1">
                                    {{ e.display_name }}
                                </td>
                                <td class="border-b border-[var(--rp-border-subtle)] px-2 py-1">
                                    {{ e.is_active ? 'Так' : 'Ні' }}
                                </td>
                                <td class="border-b border-[var(--rp-border-subtle)] px-2 py-1">
                                    <div class="flex flex-wrap gap-1">
                                        <button
                                            type="button"
                                            class="rp-focusable rp-btn rp-btn-ghost text-xs"
                                            :disabled="emoticonBusy"
                                            @click="toggleEmoticon(e)"
                                        >
                                            {{ e.is_active ? 'Вимкнути' : 'Увімкнути' }}
                                        </button>
                                        <button
                                            type="button"
                                            class="rp-focusable rp-btn rp-btn-ghost text-xs text-[var(--rp-error)]"
                                            :disabled="emoticonBusy"
                                            @click="deleteEmoticon(e)"
                                        >
                                            Видалити
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="emoticonList.length === 0">
                                <td colspan="5" class="px-2 py-4 text-center text-[var(--rp-text-muted)]">
                                    Поки немає записів. Додайте файл нижче.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <fieldset :disabled="emoticonBusy" class="mt-4 space-y-3">
                    <legend class="text-sm font-medium text-[var(--rp-text)]">Новий смайл</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="rp-label" for="emo-code">Код (латиниця, цифри, _)</label>
                            <input
                                id="emo-code"
                                v-model.trim="newEmoticon.code"
                                type="text"
                                class="rp-input rp-focusable mt-1 w-full font-mono text-sm"
                                maxlength="64"
                                autocomplete="off"
                            />
                        </div>
                        <div>
                            <label class="rp-label" for="emo-title">Відображувана назва</label>
                            <input
                                id="emo-title"
                                v-model.trim="newEmoticon.display_name"
                                type="text"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                maxlength="200"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="rp-label" for="emo-kw">Ключові слова для пошуку (необов’язково)</label>
                        <input
                            id="emo-kw"
                            v-model.trim="newEmoticon.keywords"
                            type="text"
                            class="rp-input rp-focusable mt-1 w-full text-sm"
                            maxlength="500"
                        />
                    </div>
                    <div>
                        <label class="rp-label" for="emo-sort">Порядок сортування</label>
                        <input
                            id="emo-sort"
                            v-model.number="newEmoticon.sort_order"
                            type="number"
                            min="0"
                            max="99999999"
                            class="rp-input rp-focusable mt-1 w-full max-w-xs text-sm"
                        />
                    </div>
                    <div>
                        <label class="rp-label" for="emo-file">Файл зображення</label>
                        <input
                            id="emo-file"
                            ref="emoticonFileInput"
                            type="file"
                            accept="image/gif,image/png,image/webp"
                            class="mt-1 block w-full text-sm text-[var(--rp-text-muted)] file:mr-3 file:rounded-md file:border-0 file:bg-[var(--rp-surface-elevated)] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-[var(--rp-text)]"
                            @change="onEmoticonFile"
                        />
                    </div>
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-primary text-sm"
                        @click="submitNewEmoticon"
                    >
                        {{ emoticonBusy ? 'Збереження…' : 'Додати смайл' }}
                    </button>
                </fieldset>
            </div>
        </div>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';
import { loadChatEmoticonsCatalog } from '../utils/chatEmoticons';

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
            emoticonList: [],
            emoticonLoading: false,
            emoticonBusy: false,
            emoticonError: '',
            newEmoticon: {
                code: '',
                display_name: '',
                keywords: '',
                sort_order: 0,
            },
            newEmoticonFile: null,
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
                this.emoticonError = '';
                this.load();
                this.loadEmoticonList();
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
        async loadEmoticonList() {
            this.emoticonLoading = true;
            this.emoticonError = '';
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.get('/api/v1/mod/emoticons');
                this.emoticonList = Array.isArray(data.data) ? data.data : [];
            } catch {
                this.emoticonList = [];
                this.emoticonError = 'Не вдалося завантажити список смайлів.';
            } finally {
                this.emoticonLoading = false;
            }
        },
        onEmoticonFile(ev) {
            const f = ev && ev.target && ev.target.files && ev.target.files[0];
            this.newEmoticonFile = f || null;
        },
        resetEmoticonForm() {
            this.newEmoticon = {
                code: '',
                display_name: '',
                keywords: '',
                sort_order: 0,
            };
            this.newEmoticonFile = null;
            const el = this.$refs.emoticonFileInput;
            if (el) {
                el.value = '';
            }
        },
        async submitNewEmoticon() {
            this.emoticonError = '';
            const code = (this.newEmoticon.code || '').trim();
            const title = (this.newEmoticon.display_name || '').trim();
            if (!code || !title) {
                this.emoticonError = 'Вкажіть код і назву.';

                return;
            }
            if (!this.newEmoticonFile) {
                this.emoticonError = 'Оберіть файл зображення.';

                return;
            }
            this.emoticonBusy = true;
            try {
                await this.ensureSanctum();
                const fd = new FormData();
                fd.append('code', code);
                fd.append('display_name', title);
                fd.append('sort_order', String(Number(this.newEmoticon.sort_order) || 0));
                fd.append('is_active', '1');
                const kw = (this.newEmoticon.keywords || '').trim();
                if (kw) {
                    fd.append('keywords', kw);
                }
                fd.append('file', this.newEmoticonFile);
                await window.axios.post('/api/v1/mod/emoticons', fd);
                await loadChatEmoticonsCatalog();
                await this.loadEmoticonList();
                this.resetEmoticonForm();
            } catch (e) {
                const st = e.response && e.response.status;
                const msg =
                    (e.response && e.response.data && e.response.data.message) ||
                    (st === 422 ? 'Перевірте код і файл.' : null) ||
                    'Не вдалося додати смайл.';
                this.emoticonError = typeof msg === 'string' ? msg : 'Не вдалося додати смайл.';
            } finally {
                this.emoticonBusy = false;
            }
        },
        async toggleEmoticon(row) {
            if (!row || row.id == null) {
                return;
            }
            this.emoticonError = '';
            this.emoticonBusy = true;
            try {
                await this.ensureSanctum();
                await window.axios.patch(`/api/v1/mod/emoticons/${row.id}`, {
                    is_active: !row.is_active,
                });
                await loadChatEmoticonsCatalog();
                await this.loadEmoticonList();
            } catch {
                this.emoticonError = 'Не вдалося оновити запис.';
            } finally {
                this.emoticonBusy = false;
            }
        },
        async deleteEmoticon(row) {
            if (!row || row.id == null) {
                return;
            }
            if (!window.confirm(`Видалити смайл :${row.code}: з каталогу? Файл з диска буде прибрано, якщо більше не використовується.`)) {
                return;
            }
            this.emoticonError = '';
            this.emoticonBusy = true;
            try {
                await this.ensureSanctum();
                await window.axios.delete(`/api/v1/mod/emoticons/${row.id}`);
                await loadChatEmoticonsCatalog();
                await this.loadEmoticonList();
            } catch {
                this.emoticonError = 'Не вдалося видалити запис.';
            } finally {
                this.emoticonBusy = false;
            }
        },
    },
};
</script>
