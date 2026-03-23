<template>
    <RpModal
        :open="Boolean(open && user && !user.guest)"
        variant="framed"
        size="xl"
        content-sized
        max-height-class="max-h-[92vh]"
        :aria-labelledby="titleId"
        :scroll-body="false"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3">
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">Профіль</h2>
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

        <div v-if="user && !user.guest" class="flex min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
            <div class="shrink-0">
                    <div class="rp-profile-modal-tabs flex flex-wrap gap-1">
                        <button
                            v-for="t in tabs"
                            :key="t.id"
                            type="button"
                            class="rp-focusable rp-tab px-2 py-2 text-xs sm:text-sm"
                            :aria-selected="activeTab === t.id ? 'true' : 'false'"
                            @click="activeTab = t.id"
                        >
                            {{ t.label }}
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
                    <p
                        v-if="tabError"
                        role="alert"
                        class="mb-3 rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
                    >
                        {{ tabError }}
                    </p>

                    <!-- Персональні дані -->
                    <div v-show="activeTab === 'personal'" class="space-y-4">
                        <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                            <UserAvatar
                                :src="user.avatar_url || ''"
                                :name="user.user_name"
                                variant="sidebar"
                                decorative
                            />
                            <div class="w-full space-y-2 sm:flex-1">
                                <input
                                    ref="avatarInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="rp-sr-only"
                                    @change="onAvatarFileSelected"
                                />
                                <div class="flex flex-wrap gap-2">
                                    <RpButton
                                        variant="secondary"
                                        class="text-sm"
                                        :loading="avatarUploading"
                                        :disabled="avatarUploading || user.chat_upload_disabled"
                                        @click="$refs.avatarInput && $refs.avatarInput.click()"
                                    >
                                        {{ avatarUploading ? 'Завантаження…' : 'Вибрати файл' }}
                                    </RpButton>
                                </div>
                                <p v-if="avatarUploadError" class="text-xs text-[var(--rp-error)]" role="alert">
                                    {{ avatarUploadError }}
                                </p>
                                <p class="text-xs text-[var(--rp-text-muted)]">
                                    JPEG, PNG, GIF або WebP, до 4 МБ (як у чаті).
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="user.chat_upload_disabled"
                            role="status"
                            class="rounded-md border border-amber-600/45 bg-amber-500/10 px-3 py-2 text-sm text-[var(--rp-text)]"
                        >
                            <span class="font-medium">Завантаження вимкнено модератором.</span>
                            Не можна додавати зображення в чат і змінювати аватарку, доки обмеження діє.
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <RpCountryCombobox
                                    input-id="pf-country"
                                    label="Країна"
                                    :value="personal.country"
                                    @input="personal.country = $event"
                                />
                                <label class="mt-1 flex items-center gap-2 text-xs text-[var(--rp-text-muted)]">
                                    <input v-model="personal.country_hidden" type="checkbox" class="rp-focusable" />
                                    Приховано для інших
                                </label>
                            </div>
                            <div>
                                <label class="rp-label" for="pf-region">Регіон</label>
                                <input
                                    id="pf-region"
                                    v-model.trim="personal.region"
                                    type="text"
                                    maxlength="100"
                                    class="rp-input rp-focusable w-full"
                                />
                                <label class="mt-1 flex items-center gap-2 text-xs text-[var(--rp-text-muted)]">
                                    <input v-model="personal.region_hidden" type="checkbox" class="rp-focusable" />
                                    Приховано для інших
                                </label>
                            </div>
                            <div>
                                <label class="rp-label" for="pf-age">Вік</label>
                                <input
                                    id="pf-age"
                                    v-model.number="personal.age"
                                    type="number"
                                    min="13"
                                    max="120"
                                    class="rp-input rp-focusable w-full"
                                />
                                <label class="mt-1 flex items-center gap-2 text-xs text-[var(--rp-text-muted)]">
                                    <input v-model="personal.age_hidden" type="checkbox" class="rp-focusable" />
                                    Приховано для інших
                                </label>
                            </div>
                            <div>
                                <label class="rp-label" for="pf-sex">Стать</label>
                                <select id="pf-sex" v-model="personal.sex" class="rp-input rp-focusable w-full">
                                    <option :value="null">—</option>
                                    <option value="male">Чоловік</option>
                                    <option value="female">Жінка</option>
                                    <option value="other">Інше</option>
                                    <option value="prefer_not">Не вказувати</option>
                                </select>
                                <label class="mt-1 flex items-center gap-2 text-xs text-[var(--rp-text-muted)]">
                                    <input v-model="personal.sex_hidden" type="checkbox" class="rp-focusable" />
                                    Приховано для інших
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="rp-label" for="pf-occupation">Рід занять</label>
                            <input
                                id="pf-occupation"
                                v-model.trim="personal.occupation"
                                type="text"
                                maxlength="191"
                                class="rp-input rp-focusable w-full"
                            />
                        </div>
                        <div>
                            <label class="rp-label" for="pf-about">Про мене</label>
                            <textarea
                                id="pf-about"
                                v-model.trim="personal.about"
                                rows="4"
                                maxlength="5000"
                                class="rp-input rp-focusable w-full resize-y"
                            />
                        </div>
                        <RpButton
                            class="w-full sm:w-auto"
                            :loading="saving"
                            :disabled="saving"
                            @click="savePersonal"
                        >
                            {{ saving ? 'Збереження…' : 'Зберегти персональні дані' }}
                        </RpButton>
                    </div>

                    <!-- Оформлення (T43): тема лише локально, без API -->
                    <div v-show="activeTab === 'appearance'" class="space-y-3">
                        <p class="text-sm text-[var(--rp-text-muted)]">
                            Світла, темна або як у системі. Застосовується до всього чату на цьому пристрої
                            (зберігається в браузері).
                        </p>
                        <RpButton
                            variant="secondary"
                            class="text-sm"
                            aria-label="Перемкнути тему оформлення"
                            @click="$emit('cycle-theme')"
                        >
                            {{ themeLabel }}
                        </RpButton>
                    </div>

                    <!-- Акаунт -->
                    <div v-show="activeTab === 'account'" class="space-y-3">
                        <div>
                            <label class="rp-label" for="pf-email">E-mail</label>
                            <input
                                id="pf-email"
                                v-model.trim="account.email"
                                type="email"
                                autocomplete="email"
                                class="rp-input rp-focusable w-full"
                            />
                        </div>
                        <p class="text-xs text-[var(--rp-text-muted)]">
                            Для зміни пошти або пароля введіть поточний пароль.
                        </p>
                        <div>
                            <label class="rp-label" for="pf-cur-pw">Поточний пароль</label>
                            <input
                                id="pf-cur-pw"
                                v-model="account.current_password"
                                type="password"
                                autocomplete="current-password"
                                class="rp-input rp-focusable w-full"
                            />
                        </div>
                        <div>
                            <label class="rp-label" for="pf-new-pw">Новий пароль</label>
                            <input
                                id="pf-new-pw"
                                v-model="account.password"
                                type="password"
                                autocomplete="new-password"
                                class="rp-input rp-focusable w-full"
                            />
                        </div>
                        <div>
                            <label class="rp-label" for="pf-new-pw2">Підтвердження пароля</label>
                            <input
                                id="pf-new-pw2"
                                v-model="account.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="rp-input rp-focusable w-full"
                            />
                        </div>
                        <RpButton
                            class="w-full sm:w-auto"
                            :loading="saving"
                            :disabled="saving"
                            @click="saveAccount"
                        >
                            {{ saving ? 'Збереження…' : 'Зберегти акаунт' }}
                        </RpButton>
                    </div>

                    <!-- Соцмережі -->
                    <div v-show="activeTab === 'social'" class="space-y-3">
                        <div v-for="row in socialFields" :key="row.key">
                            <label class="rp-label" :for="'soc-' + row.key">{{ row.label }}</label>
                            <input
                                :id="'soc-' + row.key"
                                v-model.trim="social[row.key]"
                                type="text"
                                maxlength="500"
                                class="rp-input rp-focusable w-full"
                            />
                        </div>
                        <RpButton
                            class="w-full sm:w-auto"
                            :loading="saving"
                            :disabled="saving"
                            @click="saveSocial"
                        >
                            {{ saving ? 'Збереження…' : 'Зберегти соцмережі' }}
                        </RpButton>
                    </div>

                    <!-- Звуки -->
                    <div v-show="activeTab === 'sounds'" class="space-y-4">
                        <p class="text-sm text-[var(--rp-text-muted)]">
                            Налаштування збережуються для майбутніх сповіщень у чаті (відтворення підключиться окремим таском).
                        </p>
                        <label class="flex items-center justify-between gap-3 text-sm text-[var(--rp-text)]">
                            <span>Звичайні повідомлення</span>
                            <input v-model="sounds.public_messages" type="checkbox" class="rp-focusable h-5 w-5" />
                        </label>
                        <label class="flex items-center justify-between gap-3 text-sm text-[var(--rp-text)]">
                            <span>Згадки (mentions)</span>
                            <input v-model="sounds.mentions" type="checkbox" class="rp-focusable h-5 w-5" />
                        </label>
                        <label class="flex items-center justify-between gap-3 text-sm text-[var(--rp-text)]">
                            <span>Приватні чати</span>
                            <input v-model="sounds.private" type="checkbox" class="rp-focusable h-5 w-5" />
                        </label>
                        <div>
                            <label class="rp-label" for="pf-vol">Гучність ({{ sounds.volume_percent }}%)</label>
                            <input
                                id="pf-vol"
                                v-model.number="sounds.volume_percent"
                                type="range"
                                min="0"
                                max="100"
                                class="rp-focusable w-full"
                            />
                        </div>
                        <RpButton
                            class="w-full sm:w-auto"
                            :loading="saving"
                            :disabled="saving"
                            @click="saveSounds"
                        >
                            {{ saving ? 'Збереження…' : 'Зберегти звуки' }}
                        </RpButton>
                    </div>
                </div>
        </div>
        <p v-else-if="!user" class="px-4 py-4 text-sm text-[var(--rp-text-muted)]">
            Завантаження профілю…
        </p>
        <p v-else class="px-4 py-4 text-sm text-[var(--rp-text-muted)]">
            Повний профіль недоступний у гостьовому режимі. Увійдіть під обліковим записом, щоб редагувати дані.
        </p>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';
import RpCountryCombobox from './ui/RpCountryCombobox.vue';
import UserAvatar from './UserAvatar.vue';
import countryRows from '../../data/iso3166-alpha2-uk.json';
import { normalizeStoredCountryCode } from '../utils/countryProfile.js';

const VALID_COUNTRY_CODES = new Set(countryRows.map((r) => r.code));

let titleSeq = 0;

export default {
    name: 'UserProfileModal',
    components: { RpModal, RpCountryCombobox, UserAvatar },
    props: {
        open: {
            type: Boolean,
            default: false,
        },
        user: {
            type: Object,
            default: null,
        },
        themeLabel: {
            type: String,
            default: '',
        },
    },
    data() {
        titleSeq += 1;

        return {
            titleId: `user-profile-title-${titleSeq}`,
            activeTab: 'personal',
            saving: false,
            tabError: '',
            avatarUploading: false,
            avatarUploadError: '',
            personal: {
                country: '',
                region: '',
                age: null,
                sex: null,
                country_hidden: false,
                region_hidden: false,
                age_hidden: false,
                sex_hidden: false,
                occupation: '',
                about: '',
            },
            account: {
                email: '',
                current_password: '',
                password: '',
                password_confirmation: '',
            },
            social: {
                facebook: '',
                instagram: '',
                telegram: '',
                twitter: '',
                youtube: '',
                tiktok: '',
                discord: '',
                website: '',
            },
            sounds: {
                public_messages: true,
                mentions: true,
                private: true,
                volume_percent: 80,
            },
            tabs: [
                { id: 'personal', label: 'Персональні' },
                { id: 'appearance', label: 'Оформлення' },
                { id: 'account', label: 'Акаунт' },
                { id: 'social', label: 'Соцмережі' },
                { id: 'sounds', label: 'Звуки' },
            ],
            socialFields: [
                { key: 'facebook', label: 'Facebook' },
                { key: 'instagram', label: 'Instagram' },
                { key: 'telegram', label: 'Telegram' },
                { key: 'twitter', label: 'X / Twitter' },
                { key: 'youtube', label: 'YouTube' },
                { key: 'tiktok', label: 'TikTok' },
                { key: 'discord', label: 'Discord' },
                { key: 'website', label: 'Сайт' },
            ],
        };
    },
    watch: {
        open(v) {
            if (v) {
                this.tabError = '';
                this.activeTab = 'personal';
                this.syncFromUser();
            }
        },
        user: {
            deep: true,
            handler() {
                if (this.open) {
                    this.syncFromUser();
                }
            },
        },
    },
    methods: {
        close() {
            this.$emit('close');
        },
        syncFromUser() {
            const u = this.user;
            if (!u || u.guest) {
                return;
            }
            const pr = u.profile || {};
            this.personal = {
                country: normalizeStoredCountryCode(pr.country, VALID_COUNTRY_CODES),
                region: pr.region != null ? String(pr.region) : '',
                age: pr.age != null && pr.age !== '' ? Number(pr.age) : null,
                sex: pr.sex != null ? pr.sex : null,
                country_hidden: Boolean(pr.country_hidden),
                region_hidden: Boolean(pr.region_hidden),
                age_hidden: Boolean(pr.age_hidden),
                sex_hidden: Boolean(pr.sex_hidden),
                occupation: pr.occupation != null ? String(pr.occupation) : '',
                about: pr.about != null ? String(pr.about) : '',
            };
            this.account = {
                email: u.email != null ? String(u.email) : '',
                current_password: '',
                password: '',
                password_confirmation: '',
            };
            const sl = u.social_links || {};
            const keys = Object.keys(this.social);
            keys.forEach((k) => {
                this.social[k] = sl[k] != null ? String(sl[k]) : '';
            });
            const snd = u.notification_sound_prefs || {};
            this.sounds = {
                public_messages: snd.public_messages !== false,
                mentions: snd.mentions !== false,
                private: snd.private !== false,
                volume_percent:
                    snd.volume_percent != null ? Math.min(100, Math.max(0, Number(snd.volume_percent))) : 80,
            };
        },
        async ensureSanctum() {
            await window.axios.get('/sanctum/csrf-cookie');
        },
        formatValidationMessage(err) {
            const d = err.response && err.response.data;
            if (!d) {
                return 'Не вдалося зберегти.';
            }
            if (d.errors && typeof d.errors === 'object') {
                const first = Object.keys(d.errors).find((k) => Array.isArray(d.errors[k]) && d.errors[k].length);
                if (first) {
                    return d.errors[first][0];
                }
            }
            if (d.message) {
                return String(d.message);
            }

            return 'Не вдалося зберегти.';
        },
        async savePersonal() {
            this.tabError = '';
            this.saving = true;
            await this.ensureSanctum();
            try {
                const ageVal = this.personal.age === '' || this.personal.age === null ? null : Number(this.personal.age);
                const { data } = await window.axios.patch('/api/v1/me/profile', {
                    profile: {
                        country: this.personal.country ? String(this.personal.country).trim().toUpperCase() : null,
                        region: this.personal.region || null,
                        age: Number.isFinite(ageVal) ? ageVal : null,
                        sex: this.personal.sex,
                        country_hidden: this.personal.country_hidden,
                        region_hidden: this.personal.region_hidden,
                        age_hidden: this.personal.age_hidden,
                        sex_hidden: this.personal.sex_hidden,
                        occupation: this.personal.occupation || null,
                        about: this.personal.about || null,
                    },
                });
                if (data.data) {
                    this.$emit('updated', data.data);
                }
            } catch (e) {
                this.tabError = this.formatValidationMessage(e);
            } finally {
                this.saving = false;
            }
        },
        async saveAccount() {
            this.tabError = '';
            this.saving = true;
            await this.ensureSanctum();
            const body = {
                current_password: this.account.current_password,
            };
            if (this.account.email && this.account.email !== (this.user && this.user.email)) {
                body.email = this.account.email;
            }
            if (this.account.password) {
                body.password = this.account.password;
                body.password_confirmation = this.account.password_confirmation;
            }
            try {
                const { data } = await window.axios.patch('/api/v1/me/account', body);
                if (data.data) {
                    this.$emit('updated', data.data);
                    this.account.current_password = '';
                    this.account.password = '';
                    this.account.password_confirmation = '';
                }
            } catch (e) {
                this.tabError = this.formatValidationMessage(e);
            } finally {
                this.saving = false;
            }
        },
        async saveSocial() {
            this.tabError = '';
            this.saving = true;
            await this.ensureSanctum();
            try {
                const { data } = await window.axios.patch('/api/v1/me/profile', {
                    social_links: { ...this.social },
                });
                if (data.data) {
                    this.$emit('updated', data.data);
                }
            } catch (e) {
                this.tabError = this.formatValidationMessage(e);
            } finally {
                this.saving = false;
            }
        },
        async saveSounds() {
            this.tabError = '';
            this.saving = true;
            await this.ensureSanctum();
            try {
                const { data } = await window.axios.patch('/api/v1/me/profile', {
                    notification_sound_prefs: {
                        public_messages: this.sounds.public_messages,
                        mentions: this.sounds.mentions,
                        private: this.sounds.private,
                        volume_percent: this.sounds.volume_percent,
                    },
                });
                if (data.data) {
                    this.$emit('updated', data.data);
                }
            } catch (e) {
                this.tabError = this.formatValidationMessage(e);
            } finally {
                this.saving = false;
            }
        },
        async onAvatarFileSelected(e) {
            const input = e.target;
            const file = input.files && input.files[0];
            if (!file || !this.user || this.user.guest) {
                return;
            }
            if (this.user.chat_upload_disabled) {
                this.avatarUploadError =
                    'Завантаження зображень вимкнено модератором. Зверніться до персоналу чату.';

                return;
            }
            this.avatarUploadError = '';
            this.avatarUploading = true;
            await this.ensureSanctum();
            try {
                const form = new FormData();
                form.append('image', file);
                const { data } = await window.axios.post('/api/v1/me/avatar', form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                if (data.data) {
                    this.$emit('updated', data.data);
                }
            } catch (err) {
                this.avatarUploadError =
                    err.response?.data?.message ||
                    (err.response?.status === 403
                        ? 'Гості не можуть завантажувати аватарку.'
                        : 'Не вдалося оновити аватарку.');
            } finally {
                this.avatarUploading = false;
                input.value = '';
            }
        },
    },
};
</script>
