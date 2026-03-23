<template>
    <div class="flex min-h-screen flex-col bg-[var(--rp-bg)] px-4 py-6 sm:px-6">
        <header class="mx-auto mb-6 flex w-full max-w-5xl flex-wrap items-center justify-between gap-3">
            <div class="flex min-w-0 flex-wrap items-center gap-3">
                <router-link
                    :to="{ name: 'chat', query: roomQuery }"
                    class="rp-focusable shrink-0 text-sm font-medium text-[var(--rp-link)] hover:text-[var(--rp-link-hover)]"
                >
                    ← До чату
                </router-link>
                <div class="min-w-0">
                    <h1 class="text-lg font-semibold text-[var(--rp-text)] sm:text-xl">
                        Користувачі (персонал)
                    </h1>
                    <p class="mt-0.5 text-sm text-[var(--rp-text-muted)]">
                        Каталог, фільтри, масові дії та створення — лише для адміністратора чату.
                    </p>
                </div>
            </div>
            <button
                type="button"
                class="rp-focusable rp-btn rp-btn-ghost text-sm"
                aria-label="Перемкнути тему оформлення"
                @click="cycleTheme"
            >
                {{ themeLabel }}
            </button>
        </header>

        <main id="main-content" class="mx-auto w-full max-w-5xl flex-1 space-y-4" tabindex="-1">
            <div v-if="!viewerIsAdmin" class="rp-banner" role="alert">
                Доступ лише для адміністратора чату.
            </div>

            <template v-else>
                <div class="rp-panel space-y-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-secondary text-sm"
                            :disabled="loading"
                            @click="openCreateModal"
                        >
                            Новий користувач
                        </button>
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-ghost text-sm"
                            :disabled="loading"
                            @click="reloadCatalog"
                        >
                            Оновити список
                        </button>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <label class="rp-label rp-sr-only" for="staff-user-q">Пошук</label>
                        <input
                            id="staff-user-q"
                            v-model.trim="searchInput"
                            type="search"
                            maxlength="191"
                            class="rp-input rp-focusable min-w-[12rem] flex-1"
                            placeholder="Нік, id або e-mail…"
                            :disabled="loading"
                            @keyup.enter="applySearch"
                        />
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-primary shrink-0"
                            :disabled="loading"
                            @click="applySearch"
                        >
                            Шукати
                        </button>
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-ghost shrink-0 text-sm"
                            :disabled="loading"
                            @click="clearSearchToCatalog"
                        >
                            Каталог
                        </button>
                    </div>

                    <div
                        class="grid gap-3 border-t border-[var(--rp-border-subtle)] pt-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div>
                            <label class="rp-label text-xs" for="f-guest">Гість</label>
                            <select
                                id="f-guest"
                                v-model="filterGuest"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="">
                                    Усі
                                </option>
                                <option value="1">
                                    Лише гості
                                </option>
                                <option value="0">
                                    Без гостей
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="rp-label text-xs" for="f-rank">Ранг</label>
                            <select
                                id="f-rank"
                                v-model="filterRank"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="">
                                    Усі
                                </option>
                                <option value="0">
                                    Користувач (0)
                                </option>
                                <option value="1">
                                    Модератор (1)
                                </option>
                                <option value="2">
                                    Адмін (2)
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="rp-label text-xs" for="f-vip">VIP</label>
                            <select
                                id="f-vip"
                                v-model="filterVip"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="">
                                    Усі
                                </option>
                                <option value="1">
                                    VIP
                                </option>
                                <option value="0">
                                    Не VIP
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="rp-label text-xs" for="f-muted">Мут зараз</label>
                            <select
                                id="f-muted"
                                v-model="filterMuted"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="">
                                    Усі
                                </option>
                                <option value="1">
                                    Активний
                                </option>
                                <option value="0">
                                    Ні
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="rp-label text-xs" for="f-kick">Kick зараз</label>
                            <select
                                id="f-kick"
                                v-model="filterKicked"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="">
                                    Усі
                                </option>
                                <option value="1">
                                    Активний
                                </option>
                                <option value="0">
                                    Ні
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="rp-label text-xs" for="f-dis">Обліковий запис</label>
                            <select
                                id="f-dis"
                                v-model="filterDisabled"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="">
                                    Усі
                                </option>
                                <option value="1">
                                    Вимкнено
                                </option>
                                <option value="0">
                                    Активні
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="rp-label text-xs" for="f-sort">Сортування</label>
                            <select
                                id="f-sort"
                                v-model="sortField"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="id">
                                    Id
                                </option>
                                <option value="user_name">
                                    Нік
                                </option>
                                <option value="created_at">
                                    Створено
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="rp-label text-xs" for="f-dir">Порядок</label>
                            <select
                                id="f-dir"
                                v-model="sortDirection"
                                class="rp-input rp-focusable mt-1 w-full text-sm"
                                :disabled="loading"
                                @change="applyFilters"
                            >
                                <option value="desc">
                                    Спадний
                                </option>
                                <option value="asc">
                                    Зростання
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <div
                    v-if="selectedIds.length"
                    class="rp-panel flex flex-wrap items-end gap-3 border border-[var(--rp-border-subtle)]"
                    role="region"
                    aria-label="Масові дії"
                >
                    <p class="text-sm text-[var(--rp-text)]">
                        Обрано: <strong>{{ selectedIds.length }}</strong>
                    </p>
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-secondary text-sm"
                        :disabled="loading"
                        @click="openBulkModal"
                    >
                        Масова дія…
                    </button>
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-ghost text-sm"
                        :disabled="loading"
                        @click="clearSelection"
                    >
                        Скинути вибір
                    </button>
                </div>

                <p v-if="loadError" class="rp-banner" role="alert">
                    {{ loadError }}
                </p>
                <p
                    v-if="statusMsg"
                    class="text-sm text-[var(--rp-text-muted)]"
                    role="status"
                    aria-live="polite"
                    aria-atomic="true"
                >
                    {{ statusMsg }}
                </p>

                <p v-if="loading" class="text-sm text-[var(--rp-text-muted)]" role="status">
                    Завантаження…
                </p>

                <div
                    v-else
                    class="mt-4 overflow-x-auto rounded-md border border-[var(--rp-border-subtle)]"
                >
                    <table class="w-full min-w-[42rem] border-collapse text-left text-sm text-[var(--rp-text)]">
                        <thead class="bg-[var(--rp-surface-elevated)] text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                            <tr>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-2 py-2">
                                    <span class="rp-sr-only">Вибір</span>
                                    <input
                                        type="checkbox"
                                        class="rp-focusable h-4 w-4 rounded border border-[var(--rp-border-subtle)]"
                                        aria-label="Обрати всіх керованих на сторінці"
                                        :checked="allManageableSelected"
                                        @change="toggleSelectAllPage"
                                    />
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Id
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Нік
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Роль
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    Статус
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    VIP
                                </th>
                                <th scope="col" class="border-b border-[var(--rp-border-subtle)] px-3 py-2">
                                    E-mail
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="r in rows"
                                :key="r.id"
                                tabindex="0"
                                :class="[
                                    'rp-table-row-interactive border-b border-[var(--rp-border-subtle)]',
                                    selected && selected.id === r.id ? 'bg-[var(--rp-surface-elevated)]' : '',
                                ]"
                                :aria-label="`Користувач ${r.user_name}, id ${r.id}. Натисніть Enter або пробіл, щоб відкрити панель редагування.`"
                                @click="selectRow(r)"
                                @keydown.enter.prevent="selectRow(r)"
                                @keydown.space.prevent="selectRow(r)"
                            >
                                <td class="px-2 py-2" @click.stop>
                                    <input
                                        type="checkbox"
                                        class="rp-focusable h-4 w-4 rounded border border-[var(--rp-border-subtle)]"
                                        :disabled="!r.can_manage"
                                        :checked="selectedIds.includes(r.id)"
                                        :aria-label="`Обрати ${r.user_name}`"
                                        @change="toggleRowSelected(r)"
                                    />
                                </td>
                                <td class="px-3 py-2 font-mono text-xs">
                                    {{ r.id }}
                                </td>
                                <td class="px-3 py-2 font-medium">
                                    {{ r.user_name }}
                                </td>
                                <td class="px-3 py-2">
                                    {{ roleLabel(r.chat_role) }}
                                </td>
                                <td class="px-3 py-2 text-xs">
                                    <span v-if="r.account_disabled" class="text-[var(--rp-error)]">вимкн.</span>
                                    <span v-if="r.muted_active" class="ml-1 text-[var(--rp-text-muted)]">мут</span>
                                    <span v-if="r.kicked_active" class="ml-1 text-[var(--rp-text-muted)]">kick</span>
                                    <span
                                        v-if="!r.account_disabled && !r.muted_active && !r.kicked_active"
                                        class="text-[var(--rp-text-muted)]"
                                    >—</span>
                                </td>
                                <td class="px-3 py-2">
                                    {{ r.vip ? 'Так' : 'Ні' }}
                                </td>
                                <td class="max-w-[14rem] truncate px-3 py-2 text-xs">
                                    {{ r.email || '—' }}
                                </td>
                            </tr>
                            <tr v-if="rows.length === 0">
                                <td colspan="7" class="px-3 py-8 text-center text-[var(--rp-text-muted)]">
                                    Немає рядків за поточними умовами.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <nav
                    v-if="meta && meta.last_page > 1"
                    class="flex flex-wrap items-center justify-between gap-3 border-t border-[var(--rp-border-subtle)] pt-4"
                    aria-label="Пагінація"
                >
                    <p class="text-sm text-[var(--rp-text-muted)]">
                        Сторінка {{ meta.current_page }} з {{ meta.last_page }} (усього {{ meta.total }})
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-ghost text-sm"
                            :disabled="loading || meta.current_page <= 1"
                            @click="goPage(meta.current_page - 1)"
                        >
                            Попередня
                        </button>
                        <button
                            type="button"
                            class="rp-focusable rp-btn rp-btn-ghost text-sm"
                            :disabled="loading || meta.current_page >= meta.last_page"
                            @click="goPage(meta.current_page + 1)"
                        >
                            Наступна
                        </button>
                    </div>
                </nav>

                <div
                    v-if="selected"
                    class="rp-panel space-y-4 border border-[var(--rp-border-subtle)]"
                    role="region"
                    aria-label="Редагування обраного користувача"
                >
                    <h2 class="text-base font-semibold text-[var(--rp-text)]">
                        Редагування: {{ selected.user_name }} (id {{ selected.id }})
                    </h2>

                    <p v-if="!selected.can_manage" class="text-sm text-[var(--rp-text-muted)]">
                        Недостатньо прав змінювати цього користувача (вищий або рівний ранг).
                    </p>

                    <template v-else>
                        <div v-if="!selected.guest" class="space-y-3">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                                Профіль
                            </p>
                            <div>
                                <label class="rp-label" for="staff-about">Про мене</label>
                                <textarea
                                    id="staff-about"
                                    v-model="draftAbout"
                                    rows="3"
                                    maxlength="5000"
                                    class="rp-input rp-focusable mt-1 w-full"
                                />
                            </div>
                            <div>
                                <label class="rp-label" for="staff-occupation">Рід занять</label>
                                <input
                                    id="staff-occupation"
                                    v-model="draftOccupation"
                                    type="text"
                                    maxlength="191"
                                    class="rp-input rp-focusable mt-1 w-full"
                                />
                            </div>
                            <template v-if="viewerIsAdmin">
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <RpCountryCombobox
                                            input-id="staff-country"
                                            label="Країна"
                                            class="mt-1"
                                            :value="draftCountry"
                                            @input="draftCountry = $event"
                                        />
                                    </div>
                                    <div>
                                        <label class="rp-label" for="staff-region">Регіон</label>
                                        <input
                                            id="staff-region"
                                            v-model="draftRegion"
                                            type="text"
                                            maxlength="100"
                                            class="rp-input rp-focusable mt-1 w-full"
                                        />
                                    </div>
                                </div>
                            </template>
                            <button
                                type="button"
                                class="rp-focusable rp-btn rp-btn-secondary text-sm"
                                :disabled="savingProfile"
                                @click="saveProfile"
                            >
                                Зберегти профіль
                            </button>
                        </div>
                        <p v-else class="text-sm text-[var(--rp-text-muted)]">
                            Профіль гостя тут не редагується.
                        </p>

                        <div class="space-y-2 border-t border-[var(--rp-border-subtle)] pt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-[var(--rp-text-muted)]">
                                Ролі, VIP та обліковий запис
                            </p>
                            <label class="flex cursor-pointer items-center gap-2 text-sm">
                                <input
                                    v-model="draftVip"
                                    type="checkbox"
                                    class="rp-focusable h-4 w-4 rounded border border-[var(--rp-border-subtle)]"
                                    :disabled="savingRoles || selected.guest"
                                />
                                VIP
                            </label>
                            <label
                                v-if="!selected.guest"
                                class="flex cursor-pointer items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="draftAccountDisabled"
                                    type="checkbox"
                                    class="rp-focusable h-4 w-4 rounded border border-[var(--rp-border-subtle)]"
                                    :disabled="savingRoles"
                                />
                                Обліковий запис вимкнено (не може увійти)
                            </label>
                            <div v-if="viewerIsAdmin" class="max-w-xs">
                                <label class="rp-label" for="staff-rank">Ранг (user_rank)</label>
                                <select
                                    id="staff-rank"
                                    v-model.number="draftRank"
                                    class="rp-input rp-focusable mt-1 w-full"
                                    :disabled="savingRoles"
                                >
                                    <option :value="0">
                                        Користувач (0)
                                    </option>
                                    <option :value="1">
                                        Модератор (1)
                                    </option>
                                    <option :value="2">
                                        Адміністратор (2)
                                    </option>
                                </select>
                            </div>
                            <button
                                type="button"
                                class="rp-focusable rp-btn rp-btn-primary text-sm"
                                :disabled="savingRoles || selected.guest"
                                @click="saveRoles"
                            >
                                Зберегти ролі та статус облікового запису
                            </button>
                        </div>
                    </template>
                </div>
            </template>
        </main>

        <RpModal
            :open="bulkModalOpen"
            variant="framed"
            title="Масова дія"
            @close="closeBulkModal"
        >
            <div class="space-y-3 p-4">
                <div>
                    <label class="rp-label" for="bulk-action">Дія</label>
                    <select
                        id="bulk-action"
                        v-model="bulkAction"
                        class="rp-input rp-focusable mt-1 w-full"
                    >
                        <option value="set_vip">
                            Увімкнути VIP
                        </option>
                        <option value="clear_vip">
                            Вимкнути VIP
                        </option>
                        <option value="set_rank">
                            Змінити ранг
                        </option>
                        <option value="mute">
                            Мут (хв)
                        </option>
                        <option value="clear_mute">
                            Зняти мут
                        </option>
                        <option value="kick">
                            Kick (хв)
                        </option>
                        <option value="clear_kick">
                            Зняти kick
                        </option>
                        <option value="disable_account">
                            Вимкнути обліковий запис
                        </option>
                        <option value="enable_account">
                            Увімкнути обліковий запис
                        </option>
                    </select>
                </div>
                <div v-if="bulkAction === 'set_rank'">
                    <label class="rp-label" for="bulk-rank">Новий ранг</label>
                    <select
                        id="bulk-rank"
                        v-model.number="bulkRank"
                        class="rp-input rp-focusable mt-1 w-full"
                    >
                        <option :value="0">
                            0 — користувач
                        </option>
                        <option :value="1">
                            1 — модератор
                        </option>
                        <option :value="2">
                            2 — адмін
                        </option>
                    </select>
                </div>
                <div v-if="bulkNeedsMinutes">
                    <label class="rp-label" for="bulk-min">Хвилин</label>
                    <input
                        id="bulk-min"
                        v-model.number="bulkMinutes"
                        type="number"
                        min="1"
                        max="525600"
                        class="rp-input rp-focusable mt-1 w-full"
                    />
                </div>
            </div>
            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 border-t border-[var(--rp-border-subtle)] px-4 py-4 sm:flex-row sm:justify-end"
                >
                    <button type="button" class="rp-focusable rp-btn rp-btn-ghost text-sm" @click="closeBulkModal">
                        Скасувати
                    </button>
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-primary text-sm"
                        :disabled="bulkSubmitting"
                        @click="submitBulk"
                    >
                        Застосувати
                    </button>
                </div>
            </template>
        </RpModal>

        <RpModal
            :open="createModalOpen"
            variant="framed"
            title="Новий користувач"
            @close="closeCreateModal"
        >
            <div class="space-y-3 p-4">
                <div>
                    <label class="rp-label" for="c-name">Нік</label>
                    <input
                        id="c-name"
                        v-model.trim="createUserName"
                        type="text"
                        maxlength="191"
                        class="rp-input rp-focusable mt-1 w-full"
                        autocomplete="off"
                    />
                </div>
                <div>
                    <label class="rp-label" for="c-mail">E-mail</label>
                    <input
                        id="c-mail"
                        v-model.trim="createEmail"
                        type="email"
                        maxlength="255"
                        class="rp-input rp-focusable mt-1 w-full"
                        autocomplete="off"
                    />
                </div>
                <div>
                    <label class="rp-label" for="c-pass">Пароль (необов’язково)</label>
                    <input
                        id="c-pass"
                        v-model="createPassword"
                        type="password"
                        class="rp-input rp-focusable mt-1 w-full"
                        autocomplete="new-password"
                    />
                    <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                        Якщо залишити порожнім — згенерується автоматично (покажемо один раз після створення).
                    </p>
                </div>
            </div>
            <template #footer>
                <div
                    class="flex flex-col-reverse gap-2 border-t border-[var(--rp-border-subtle)] px-4 py-4 sm:flex-row sm:justify-end"
                >
                    <button type="button" class="rp-focusable rp-btn rp-btn-ghost text-sm" @click="closeCreateModal">
                        Скасувати
                    </button>
                    <button
                        type="button"
                        class="rp-focusable rp-btn rp-btn-primary text-sm"
                        :disabled="createSubmitting"
                        @click="submitCreateUser"
                    >
                        Створити
                    </button>
                </div>
            </template>
        </RpModal>
    </div>
</template>

<script>
import RpModal from '../components/RpModal.vue';
import RpCountryCombobox from '../components/ui/RpCountryCombobox.vue';
import countryRows from '../../data/iso3166-alpha2-uk.json';
import { normalizeStoredCountryCode } from '../utils/countryProfile.js';

const THEME_KEY = 'redpanda-theme';
const VALID_COUNTRY_CODES = new Set(countryRows.map((r) => r.code));

export default {
    name: 'StaffUsersView',
    components: { RpModal, RpCountryCombobox },
    data() {
        return {
            user: null,
            themeUi: 'system',
            loading: false,
            loadError: '',
            statusMsg: '',
            rows: [],
            meta: null,
            page: 1,
            searchInput: '',
            appliedSearch: '',
            listUsesSearch: false,
            filterGuest: '',
            filterRank: '',
            filterVip: '',
            filterMuted: '',
            filterKicked: '',
            filterDisabled: '',
            sortField: 'id',
            sortDirection: 'desc',
            selected: null,
            selectedIds: [],
            draftVip: false,
            draftRank: 0,
            draftAccountDisabled: false,
            draftAbout: '',
            draftOccupation: '',
            draftCountry: '',
            draftRegion: '',
            savingRoles: false,
            savingProfile: false,
            bulkModalOpen: false,
            bulkAction: 'set_vip',
            bulkMinutes: 60,
            bulkRank: 0,
            bulkSubmitting: false,
            createModalOpen: false,
            createUserName: '',
            createEmail: '',
            createPassword: '',
            createSubmitting: false,
        };
    },
    computed: {
        themeLabel() {
            if (this.themeUi === 'light') {
                return 'Тема: світла';
            }
            if (this.themeUi === 'dark') {
                return 'Тема: темна';
            }

            return 'Тема: як у системі';
        },
        viewerIsAdmin() {
            return this.user && this.user.chat_role === 'admin';
        },
        roomQuery() {
            const r = this.$route.query.room;

            return r != null && r !== '' ? { room: String(r) } : {};
        },
        manageableOnPage() {
            return this.rows.filter((r) => r.can_manage);
        },
        manageableIdsOnPage() {
            return this.manageableOnPage.map((r) => r.id);
        },
        allManageableSelected() {
            const ids = this.manageableIdsOnPage;
            return ids.length > 0 && ids.every((id) => this.selectedIds.includes(id));
        },
        bulkNeedsMinutes() {
            return this.bulkAction === 'mute' || this.bulkAction === 'kick';
        },
    },
    watch: {
        '$route.query.room'() {
            /* keep roomQuery computed in sync */
        },
    },
    created() {
        this.themeUi = localStorage.getItem(THEME_KEY) || 'system';
    },
    async mounted() {
        document.documentElement.setAttribute('data-theme', this.themeUi);
        await this.bootstrap();
    },
    methods: {
        cycleTheme() {
            const order = ['system', 'light', 'dark'];
            const i = Math.max(0, order.indexOf(this.themeUi));
            const next = order[(i + 1) % order.length];
            this.themeUi = next;
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem(THEME_KEY, next);
        },
        roleLabel(role) {
            const map = {
                guest: 'Гість',
                user: 'Користувач',
                vip: 'VIP',
                moderator: 'Модератор',
                admin: 'Адміністратор',
            };

            return map[role] || role || '—';
        },
        async fetchUser() {
            try {
                const { data } = await window.axios.get('/api/v1/auth/user');

                return data.data;
            } catch {
                return null;
            }
        },
        async bootstrap() {
            this.user = await this.fetchUser();
            if (!this.user) {
                await this.$router.replace({ path: '/' });

                return;
            }
            if (!this.viewerIsAdmin) {
                await this.$router.replace({ name: 'chat', query: this.roomQuery }).catch(() => {});

                return;
            }
            await this.reloadCatalog();
        },
        buildListParams() {
            const params = {
                page: this.page,
                per_page: 20,
                sort: this.sortField,
                direction: this.sortDirection,
            };

            if (this.listUsesSearch && this.appliedSearch) {
                params.q = this.appliedSearch;
            } else {
                params.browse = 1;
            }

            if (this.filterGuest !== '') {
                params.guest = this.filterGuest === '1';
            }
            if (this.filterRank !== '') {
                params.user_rank = Number(this.filterRank);
            }
            if (this.filterVip !== '') {
                params.vip = this.filterVip === '1';
            }
            if (this.filterMuted !== '') {
                params.muted = this.filterMuted === '1';
            }
            if (this.filterKicked !== '') {
                params.kicked = this.filterKicked === '1';
            }
            if (this.filterDisabled !== '') {
                params.disabled = this.filterDisabled === '1';
            }

            return params;
        },
        selectRow(r) {
            this.selected = r;
            this.draftVip = !!r.vip;
            this.draftRank = Number(r.user_rank) || 0;
            this.draftAccountDisabled = !!r.account_disabled;
            const p = r.profile || {};
            this.draftAbout = p.about != null ? String(p.about) : '';
            this.draftOccupation = p.occupation != null ? String(p.occupation) : '';
            this.draftCountry = normalizeStoredCountryCode(p.country, VALID_COUNTRY_CODES);
            this.draftRegion = p.region != null ? String(p.region) : '';
            this.statusMsg = '';
        },
        mergeRow(updated) {
            const i = this.rows.findIndex((x) => x.id === updated.id);
            if (i >= 0) {
                this.$set(this.rows, i, updated);
            }
            if (this.selected && this.selected.id === updated.id) {
                this.selectRow(updated);
            }
        },
        async loadPage(p) {
            this.loading = true;
            this.loadError = '';
            this.page = p;
            try {
                const { data } = await window.axios.get('/api/v1/mod/users', {
                    params: { ...this.buildListParams(), page: p },
                });
                this.rows = Array.isArray(data.data) ? data.data : [];
                this.meta = data.meta || null;
                this.pruneSelectionToRows();
            } catch (e) {
                this.rows = [];
                this.meta = null;
                this.loadError =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося завантажити список.';
            } finally {
                this.loading = false;
            }
        },
        pruneSelectionToRows() {
            const allowed = new Set(this.rows.map((r) => r.id));
            this.selectedIds = this.selectedIds.filter((id) => allowed.has(id));
        },
        applySearch() {
            this.listUsesSearch = true;
            this.appliedSearch = this.searchInput;
            this.page = 1;
            this.loadError = '';
            if (!this.appliedSearch) {
                this.loadError = 'Введіть непорожній запит або поверніться до каталогу.';

                return;
            }
            this.loadPage(1);
        },
        clearSearchToCatalog() {
            this.listUsesSearch = false;
            this.appliedSearch = '';
            this.searchInput = '';
            this.page = 1;
            this.loadError = '';
            this.loadPage(1);
        },
        async reloadCatalog() {
            this.listUsesSearch = false;
            this.appliedSearch = '';
            this.page = 1;
            await this.loadPage(1);
        },
        applyFilters() {
            this.page = 1;
            this.loadPage(1);
        },
        goPage(p) {
            if (!this.meta || p < 1 || p > this.meta.last_page) {
                return;
            }
            this.loadPage(p);
        },
        toggleRowSelected(r) {
            if (!r.can_manage) {
                return;
            }
            const i = this.selectedIds.indexOf(r.id);
            if (i >= 0) {
                this.selectedIds.splice(i, 1);
            } else {
                this.selectedIds.push(r.id);
            }
        },
        toggleSelectAllPage(ev) {
            const on = ev.target.checked;
            const ids = this.manageableIdsOnPage;
            if (on) {
                const set = new Set([...this.selectedIds, ...ids]);
                this.selectedIds = Array.from(set);
            } else {
                const drop = new Set(ids);
                this.selectedIds = this.selectedIds.filter((id) => !drop.has(id));
            }
        },
        clearSelection() {
            this.selectedIds = [];
        },
        openBulkModal() {
            this.bulkModalOpen = true;
        },
        closeBulkModal() {
            this.bulkModalOpen = false;
        },
        async submitBulk() {
            if (!this.selectedIds.length) {
                return;
            }
            this.bulkSubmitting = true;
            this.statusMsg = '';
            try {
                const body = {
                    user_ids: [...this.selectedIds],
                    action: this.bulkAction,
                };
                if (this.bulkNeedsMinutes) {
                    body.minutes = Math.max(1, Number(this.bulkMinutes) || 0);
                }
                if (this.bulkAction === 'set_rank') {
                    body.user_rank = Number(this.bulkRank);
                }
                await window.axios.post('/api/v1/mod/users/bulk', body);
                this.statusMsg = 'Масову дію застосовано.';
                this.clearSelection();
                this.closeBulkModal();
                await this.loadPage(this.page);
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося виконати масову дію.';
            } finally {
                this.bulkSubmitting = false;
            }
        },
        openCreateModal() {
            this.createUserName = '';
            this.createEmail = '';
            this.createPassword = '';
            this.createModalOpen = true;
        },
        closeCreateModal() {
            this.createModalOpen = false;
        },
        async submitCreateUser() {
            if (!this.createUserName || !this.createEmail) {
                this.statusMsg = 'Вкажіть нік і e-mail.';

                return;
            }
            this.createSubmitting = true;
            this.statusMsg = '';
            try {
                const body = {
                    user_name: this.createUserName,
                    email: this.createEmail,
                };
                if (this.createPassword) {
                    body.password = this.createPassword;
                }
                const { data } = await window.axios.post('/api/v1/mod/users', body);
                let msg = `Створено користувача «${data.data.user_name}».`;
                if (data.meta && data.meta.generated_password) {
                    msg += ` Згенерований пароль (збережіть): ${data.meta.generated_password}`;
                }
                this.statusMsg = msg;
                this.closeCreateModal();
                await this.loadPage(1);
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося створити користувача.';
            } finally {
                this.createSubmitting = false;
            }
        },
        async saveRoles() {
            if (!this.selected || !this.selected.can_manage) {
                return;
            }
            this.savingRoles = true;
            this.statusMsg = '';
            try {
                const body = { vip: this.draftVip };
                if (this.viewerIsAdmin) {
                    body.user_rank = this.draftRank;
                }
                if (!this.selected.guest) {
                    body.account_disabled = this.draftAccountDisabled;
                }
                const { data } = await window.axios.patch(`/api/v1/mod/users/${this.selected.id}`, body);
                this.mergeRow(data.data);
                this.statusMsg = 'Збережено.';
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) || 'Не вдалося зберегти.';
            } finally {
                this.savingRoles = false;
            }
        },
        async saveProfile() {
            if (!this.selected || !this.selected.can_manage || this.selected.guest) {
                return;
            }
            this.savingProfile = true;
            this.statusMsg = '';
            try {
                const profile = {
                    about: this.draftAbout,
                    occupation: this.draftOccupation,
                };
                if (this.viewerIsAdmin) {
                    profile.country = this.draftCountry ? String(this.draftCountry).trim().toUpperCase() : null;
                    profile.region = this.draftRegion;
                }
                const { data } = await window.axios.patch(`/api/v1/mod/users/${this.selected.id}/profile`, {
                    profile,
                });
                this.mergeRow(data.data);
                this.statusMsg = 'Профіль збережено.';
            } catch (e) {
                this.statusMsg =
                    (e.response && e.response.data && e.response.data.message) ||
                    'Не вдалося зберегти профіль.';
            } finally {
                this.savingProfile = false;
            }
        },
    },
};
</script>
