<template>
    <RpModal
        :open="open"
        variant="framed"
        size="7xl"
        content-sized
        max-height-class="max-h-[98vh]"
        :aria-labelledby="titleId"
        :scroll-body="true"
        @close="close"
    >
        <template #header>
            <div class="flex shrink-0 items-center justify-between gap-2 border-b border-[var(--rp-border-subtle)] px-4 py-3">
                <h2 :id="titleId" class="text-base font-semibold text-[var(--rp-text)]">Налаштування чату</h2>
                <RpCloseButton @click="close" />
            </div>
        </template>

        <div class="flex min-h-0 flex-col space-y-4 px-4 py-4">
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

            <div
                class="rp-profile-modal-tabs flex flex-wrap gap-1 border-b border-[var(--rp-border-subtle)] pb-2"
                role="tablist"
                aria-label="Розділи налаштувань чату"
                @keydown="onSettingsTabKeydown"
            >
                <button
                    v-for="t in settingsTabs"
                    :id="tabElementId(t.id)"
                    :key="t.id"
                    ref="settingsTabButtons"
                    type="button"
                    role="tab"
                    class="rp-focusable rp-tab px-2 py-2 text-xs sm:text-sm"
                    :tabindex="activeSettingsTab === t.id ? 0 : -1"
                    :aria-selected="activeSettingsTab === t.id ? 'true' : 'false'"
                    :aria-controls="tabPanelId(t.id)"
                    @click="activateSettingsTab(t.id)"
                >
                    {{ t.label }}
                </button>
            </div>

            <fieldset :disabled="loading || saving" class="min-h-0 space-y-4">
                <legend class="sr-only">Параметри чату (T51, T75, T86)</legend>

                <div
                    v-show="activeSettingsTab === 'rooms'"
                    :id="tabPanelId('rooms')"
                    role="tabpanel"
                    :aria-labelledby="tabElementId('rooms')"
                    :aria-hidden="activeSettingsTab === 'rooms' ? 'false' : 'true'"
                    class="space-y-4"
                >
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
                    <label class="rp-label" for="cs-edit-window-hours">
                        Годин на редагування / видалення власного повідомлення
                    </label>
                    <input
                        id="cs-edit-window-hours"
                        v-model.number="form.message_edit_window_hours"
                        type="number"
                        min="0"
                        max="8760"
                        class="rp-input rp-focusable w-full max-w-xs"
                    />
                    <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                        Ціле ≥ 0. Звичайний зареєстрований може змінювати лише власні публічні повідомлення молодші за цей інтервал від часу відправки.
                        <strong>0</strong> — нульове вікно (фактично без права на правки). <strong>VIP</strong> та персонал (модератор, адмін) цим лімітом не обмежені.
                        Значення зберігається в базі й переважає над змінною <span class="font-mono">CHAT_MESSAGE_EDIT_WINDOW_HOURS</span> у конфігурації сервера.
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
                </div>

                <div
                    v-show="activeSettingsTab === 'slash'"
                    :id="tabPanelId('slash')"
                    role="tabpanel"
                    :aria-labelledby="tabElementId('slash')"
                    :aria-hidden="activeSettingsTab === 'slash' ? 'false' : 'true'"
                    class="space-y-6"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Slash-команди</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Обмеження на рядки, що починаються з <span class="font-mono">/</span> (окремо від ліміту на звичайні
                            повідомлення). Застосовується до кожного користувача; при перевищенні — HTTP 429.
                        </p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="rp-label" for="cs-slash-max">Максимум команд за вікно</label>
                                <input
                                    id="cs-slash-max"
                                    v-model.number="form.slash_command_max_per_window"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="rp-input rp-focusable mt-1 w-full max-w-xs"
                                />
                            </div>
                            <div>
                                <label class="rp-label" for="cs-slash-window">Тривалість вікна (секунди)</label>
                                <input
                                    id="cs-slash-window"
                                    v-model.number="form.slash_command_window_seconds"
                                    type="number"
                                    min="10"
                                    max="86400"
                                    class="rp-input rp-focusable mt-1 w-full max-w-xs"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[var(--rp-border-subtle)] pt-4">
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Антифлуд повідомлень (T125)</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Обмежує частоту <strong>звичайних</strong> повідомлень (кімната та приват) для користувачів без
                            VIP і без прав модерації/адміна. Ідемпотентний повтор з тим самим
                            <span class="font-mono">client_message_id</span> не зараховується. При перевищенні —
                            <span class="font-mono">429</span> з кодом <span class="font-mono">message_flood_limit</span>.
                        </p>
                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text)]">
                            <input
                                id="cs-flood-enabled"
                                v-model="form.message_flood_enabled"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border"
                            />
                            Увімкнути ліміт частоти
                        </label>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="rp-label" for="cs-flood-max">Максимум повідомлень за вікно</label>
                                <input
                                    id="cs-flood-max"
                                    v-model.number="form.message_flood_max_messages"
                                    type="number"
                                    min="1"
                                    max="65535"
                                    class="rp-input rp-focusable mt-1 w-full max-w-xs"
                                />
                            </div>
                            <div>
                                <label class="rp-label" for="cs-flood-window">Тривалість вікна (секунди)</label>
                                <input
                                    id="cs-flood-window"
                                    v-model.number="form.message_flood_window_seconds"
                                    type="number"
                                    min="1"
                                    max="86400"
                                    class="rp-input rp-focusable mt-1 w-full max-w-xs"
                                />
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-[var(--rp-border-subtle)] pt-4">
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Антиспам (proxycheck)</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Якщо вимкнути — перевірка proxy/VPN для реєстрації/логіну/постингу не виконується (бекенд працює
                            у режимі allow). Зручно для інцидентів або false positive.
                        </p>
                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text)]">
                            <input
                                id="cs-proxycheck-enabled"
                                v-model="form.proxycheck_enabled"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border"
                            />
                            Увімкнути proxycheck
                        </label>
                    </div>

                    <div class="border-t border-[var(--rp-border-subtle)] pt-4">
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Модерація (slash)</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Дефолтні хвилини для <span class="font-mono">/mute</span> та <span class="font-mono">/kick</span>,
                            коли модератор не вказує другий аргумент (див. T69).
                        </p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="rp-label" for="cs-mod-mute">Мут за замовчуванням (хв)</label>
                                <input
                                    id="cs-mod-mute"
                                    v-model.number="form.mod_slash_default_mute_minutes"
                                    type="number"
                                    min="1"
                                    max="525600"
                                    class="rp-input rp-focusable mt-1 w-full max-w-xs"
                                />
                            </div>
                            <div>
                                <label class="rp-label" for="cs-mod-kick">Kick за замовчуванням (хв)</label>
                                <input
                                    id="cs-mod-kick"
                                    v-model.number="form.mod_slash_default_kick_minutes"
                                    type="number"
                                    min="1"
                                    max="525600"
                                    class="rp-input rp-focusable mt-1 w-full max-w-xs"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-show="activeSettingsTab === 'media'"
                    :id="tabPanelId('media')"
                    role="tabpanel"
                    :aria-labelledby="tabElementId('media')"
                    :aria-hidden="activeSettingsTab === 'media' ? 'false' : 'true'"
                    class="space-y-6"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Звуки (T71)</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Якщо увімкнено, кімнатні та приватні сповіщення не відтворюються (slash
                            <span class="font-mono">/silent</span> теж змінює цей прапорець).
                        </p>
                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text)]">
                            <input
                                id="cs-silent-mode"
                                v-model="form.silent_mode"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border"
                            />
                            Беззвучний режим чату
                        </label>
                        <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text)]">
                            <input
                                id="cs-sound-every-post"
                                v-model="form.sound_on_every_post"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border"
                            />
                            Звук на кожен пост у кімнаті (legacy, T75)
                        </label>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Якщо ввімкнено — кімнатний newpost лунає навіть у фоновій вкладці (узгоджено з T65).
                        </p>
                    </div>

                    <div class="border-t border-[var(--rp-border-subtle)] pt-4">
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Вкладення в чат (T86)</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Максимальний розмір файлу зображення для <span class="font-mono">POST /api/v1/images</span>. Фактична
                            межа на сервері не перевищує PHP
                            <span class="font-mono">upload_max_filesize</span> — див. підказку нижче після збереження/завантаження.
                        </p>
                        <div class="mt-3">
                            <label class="rp-label" for="cs-max-att-mb">Максимум розміру файлу (МБ)</label>
                            <input
                                id="cs-max-att-mb"
                                v-model.number="form.max_attachment_mb"
                                type="number"
                                min="0.01"
                                max="100"
                                step="0.1"
                                class="rp-input rp-focusable mt-1 w-full max-w-xs"
                            />
                            <p v-if="attachmentEffectiveLabel" class="mt-1 text-xs text-[var(--rp-text-muted)]">
                                Зараз клієнти обмежені приблизно до <strong>{{ attachmentEffectiveLabel }}</strong> (мінімум з цього
                                значення та обмежень PHP).
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-show="activeSettingsTab === 'landing'"
                    :id="tabPanelId('landing')"
                    role="tabpanel"
                    :aria-labelledby="tabElementId('landing')"
                    :aria-hidden="activeSettingsTab === 'landing' ? 'false' : 'true'"
                    class="space-y-4"
                >
                    <h3 class="text-sm font-semibold text-[var(--rp-text)]">Вхідна сторінка (T75)</h3>
                    <p class="text-xs text-[var(--rp-text-muted)]">
                        Публічний зріз без авторизації: <span class="font-mono">GET /api/v1/landing</span>. Без HTML і
                        секретів.
                    </p>
                    <div class="grid gap-3">
                        <div>
                            <label class="rp-label" for="cs-lp-title">Заголовок сторінки (замість назви застосунку)</label>
                            <input
                                id="cs-lp-title"
                                v-model.trim="form.landing_settings.page_title"
                                type="text"
                                maxlength="120"
                                class="rp-input rp-focusable mt-1 w-full max-w-xl"
                                autocomplete="off"
                            />
                        </div>
                        <div>
                            <label class="rp-label" for="cs-lp-tag">Підзаголовок</label>
                            <input
                                id="cs-lp-tag"
                                v-model.trim="form.landing_settings.tagline"
                                type="text"
                                maxlength="200"
                                class="rp-input rp-focusable mt-1 w-full max-w-xl"
                                autocomplete="off"
                            />
                        </div>
                        <div>
                            <label class="rp-label" for="cs-lp-news-t">Новина — заголовок</label>
                            <input
                                id="cs-lp-news-t"
                                v-model.trim="form.landing_settings.news_title"
                                type="text"
                                maxlength="200"
                                class="rp-input rp-focusable mt-1 w-full max-w-xl"
                            />
                        </div>
                        <div>
                            <label class="rp-label" for="cs-lp-news-b">Новина — текст</label>
                            <textarea
                                id="cs-lp-news-b"
                                v-model.trim="form.landing_settings.news_body"
                                rows="4"
                                maxlength="8000"
                                class="rp-input rp-focusable mt-1 w-full max-w-2xl font-sans text-sm"
                            />
                        </div>
                        <div class="space-y-2">
                            <p class="text-xs font-medium text-[var(--rp-text-muted)]">Посилання (до 8; тут 4 рядки)</p>
                            <div
                                v-for="(link, idx) in form.landing_settings.links.slice(0, 4)"
                                :key="'lp-link-' + idx"
                                class="grid gap-2 sm:grid-cols-2"
                            >
                                <input
                                    v-model.trim="link.label"
                                    type="text"
                                    maxlength="100"
                                    :aria-label="'Підпис посилання ' + (idx + 1)"
                                    placeholder="Підпис"
                                    class="rp-input rp-focusable w-full text-sm"
                                />
                                <input
                                    :id="'cs-lp-link-url-' + idx"
                                    v-model.trim="link.url"
                                    type="text"
                                    maxlength="500"
                                    :aria-label="'URL посилання ' + (idx + 1)"
                                    placeholder="https://… або /шлях"
                                    class="rp-input rp-focusable w-full font-mono text-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-show="activeSettingsTab === 'registration'"
                    :id="tabPanelId('registration')"
                    role="tabpanel"
                    :aria-labelledby="tabElementId('registration')"
                    :aria-hidden="activeSettingsTab === 'registration' ? 'false' : 'true'"
                    class="space-y-3"
                >
                    <h3 class="text-sm font-semibold text-[var(--rp-text)]">Реєстрація (прапорці, T75)</h3>
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text)]">
                            <input
                                v-model="form.registration_flags.registration_open"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border"
                            />
                            Дозволити реєстрацію нових облікових записів
                        </label>
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-[var(--rp-text)]">
                            <input
                                v-model="form.registration_flags.show_social_login_buttons"
                                type="checkbox"
                                class="rp-focusable h-4 w-4 rounded border"
                            />
                            Показувати кнопки соц-логіну (коли з’явиться T76)
                        </label>
                        <div>
                            <label class="rp-label" for="cs-reg-min-age">Мінімальний вік (необов’язково)</label>
                            <input
                                id="cs-reg-min-age"
                                v-model.number="form.registration_flags.min_age"
                                type="number"
                                min="0"
                                max="120"
                                class="rp-input rp-focusable mt-1 w-full max-w-xs"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-show="activeSettingsTab === 'mail'"
                    :id="tabPanelId('mail')"
                    role="tabpanel"
                    :aria-labelledby="tabElementId('mail')"
                    :aria-hidden="activeSettingsTab === 'mail' ? 'false' : 'true'"
                    class="space-y-6"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Транзакційні листи (T110)</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            SMTP, пароль і <span class="font-mono">MAIL_FROM_ADDRESS</span> лишаються в середовищі сервера
                            (див. <span class="font-mono">docs/chat-v2/MAIL-SMTP.md</span>).
                        </p>
                    </div>
                    <div>
                        <label class="rp-label" for="cs-mail-from-name">Відображуване ім’я відправника</label>
                        <input
                            id="cs-mail-from-name"
                            v-model.trim="form.transactional_mail_from_name"
                            type="text"
                            maxlength="120"
                            class="rp-input rp-focusable mt-1 w-full max-w-xl"
                            placeholder="За замовчуванням — з config/mail"
                        />
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Лише ім’я; адреса листа залишається з <span class="font-mono">MAIL_FROM_*</span> у
                            <span class="font-mono">.env</span>.
                        </p>
                    </div>
                    <div
                        v-for="sec in mailTemplateSections"
                        :key="'mail-sec-' + sec.id"
                        class="rounded-md border border-[var(--rp-border-subtle)] p-3"
                    >
                        <h4 class="text-sm font-medium text-[var(--rp-text)]">{{ sec.title }}</h4>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Плейсхолдери: <span class="font-mono">{{ sec.placeholders }}</span
                            >. Порожнє HTML-тіло — шаблон за замовчуванням з коду.
                        </p>
                        <div class="mt-2 space-y-2">
                            <label class="rp-label" :for="'cs-mail-subj-' + sec.id">Тема листа</label>
                            <input
                                :id="'cs-mail-subj-' + sec.id"
                                v-model.trim="form.mail_template_overrides[sec.id].subject"
                                type="text"
                                maxlength="200"
                                class="rp-input rp-focusable w-full text-sm"
                            />
                            <label class="rp-label" :for="'cs-mail-html-' + sec.id">HTML-тіло (фрагмент)</label>
                            <textarea
                                :id="'cs-mail-html-' + sec.id"
                                v-model="form.mail_template_overrides[sec.id].html_body"
                                rows="6"
                                class="rp-input rp-focusable w-full font-mono text-xs"
                            />
                            <label class="rp-label" :for="'cs-mail-text-' + sec.id">Текстова версія (необов’язково)</label>
                            <textarea
                                :id="'cs-mail-text-' + sec.id"
                                v-model="form.mail_template_overrides[sec.id].text_body"
                                rows="4"
                                class="rp-input rp-focusable w-full font-mono text-xs"
                            />
                            <details class="text-xs text-[var(--rp-text-muted)]">
                                <summary class="cursor-pointer text-[var(--rp-text)]">Прев’ю HTML (тестові дані)</summary>
                                <pre
                                    class="mt-2 max-h-40 overflow-auto rounded border border-[var(--rp-border-subtle)] bg-[var(--rp-bg-subtle)] p-2 font-mono text-[10px] leading-snug text-[var(--rp-text)]"
                                >{{ mailPreviewHtml(sec.id) }}</pre>
                            </details>
                        </div>
                    </div>
                </div>

                <div
                    v-show="activeSettingsTab === 'system_bot'"
                    :id="tabPanelId('system_bot')"
                    role="tabpanel"
                    :aria-labelledby="tabElementId('system_bot')"
                    :aria-hidden="activeSettingsTab === 'system_bot' ? 'false' : 'true'"
                    class="space-y-4"
                >
                    <div>
                        <h3 class="text-sm font-semibold text-[var(--rp-text)]">Системний бот «Руда панда»</h3>
                        <p class="mt-1 text-xs text-[var(--rp-text-muted)]">
                            Оголошення в стрічці та кнопка переходу до нової кімнати (T149–T150). Аватар — як у профілі
                            користувача (T151). Нік — літери, цифри, <span class="font-mono">_</span> та
                            <span class="font-mono">-</span> (без пробілів), мінімум 2 символи.
                        </p>
                    </div>
                    <p
                        v-if="botProfileError"
                        role="alert"
                        class="rounded-md border border-[var(--rp-border-subtle)] bg-[var(--rp-error-bg)] px-2 py-1.5 text-sm text-[var(--rp-error)]"
                    >
                        {{ botProfileError }}
                    </p>
                    <p v-else-if="botProfileLoading" class="text-sm text-[var(--rp-text-muted)]" role="status">
                        Завантаження профілю бота…
                    </p>
                    <p v-else-if="botProfileMissing" class="text-sm text-[var(--rp-text-muted)]">
                        Системного бота не знайдено (міграції та сидер <span class="font-mono">SystemBotUserSeeder</span>).
                    </p>
                    <template v-else>
                        <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                            <UserAvatar
                                :src="botAvatarUrl"
                                :name="botForm.user_name || 'Бот'"
                                variant="sidebar"
                                decorative
                            />
                            <div class="w-full space-y-2 sm:flex-1">
                                <input
                                    ref="botAvatarInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="rp-sr-only"
                                    @change="onBotAvatarFileSelected"
                                />
                                <RpButton
                                    variant="secondary"
                                    class="text-sm"
                                    :loading="botAvatarUploading"
                                    :disabled="botAvatarUploading || loading"
                                    @click="onBotAvatarPickClick"
                                >
                                    {{ botAvatarUploading ? 'Завантаження…' : 'Змінити аватарку' }}
                                </RpButton>
                                <p
                                    v-if="botAvatarError"
                                    role="alert"
                                    class="text-xs text-[var(--rp-error)]"
                                >
                                    {{ botAvatarError }}
                                </p>
                                <p class="text-xs text-[var(--rp-text-muted)]">
                                    JPEG, PNG, GIF або WebP; обмеження як для звичайного аватара.
                                </p>
                            </div>
                        </div>
                        <div>
                            <label class="rp-label" for="cs-bot-name">Нік у чаті</label>
                            <input
                                id="cs-bot-name"
                                v-model.trim="botForm.user_name"
                                type="text"
                                minlength="2"
                                maxlength="191"
                                class="rp-input rp-focusable mt-1 w-full max-w-xl"
                                autocomplete="off"
                            />
                        </div>
                        <RpCountryCombobox
                            input-id="cs-bot-country"
                            label="Країна (необов’язково)"
                            :value="botForm.profile.country || ''"
                            @input="onBotCountryInput"
                        />
                        <div>
                            <label class="rp-label" for="cs-bot-about">Про мене</label>
                            <textarea
                                id="cs-bot-about"
                                v-model="botForm.profile.about"
                                rows="4"
                                maxlength="5000"
                                class="rp-input rp-focusable mt-1 w-full max-w-2xl font-sans text-sm"
                            />
                        </div>
                        <RpButton
                            class="text-sm"
                            :loading="botProfileSaving"
                            :disabled="botProfileSaving || loading"
                            @click="saveBotProfile"
                        >
                            {{ botProfileSaving ? 'Збереження…' : 'Зберегти профіль бота' }}
                        </RpButton>
                    </template>
                </div>

            </fieldset>

            <div class="border-t border-[var(--rp-border-subtle)] pt-4">
                <div class="flex flex-wrap items-center gap-2">
                    <RpButton
                        v-if="activeSettingsTab !== 'system_bot'"
                        class="text-sm"
                        :loading="saving"
                        :disabled="saving || loading"
                        @click="save"
                    >
                        {{ saving ? 'Збереження…' : 'Зберегти' }}
                    </RpButton>
                    <p v-else class="text-xs text-[var(--rp-text-muted)]">
                        Загальні налаштування чату зберігаються кнопкою «Зберегти» на інших вкладках.
                    </p>
                    <p v-if="activeSettingsTab === 'emoticons'" class="text-xs text-[var(--rp-text-muted)]">
                        Зберігає всі поля налаштувань чату з інших вкладок.
                    </p>
                </div>
            </div>

            <div
                v-show="activeSettingsTab === 'emoticons'"
                :id="tabPanelId('emoticons')"
                role="tabpanel"
                :aria-labelledby="tabElementId('emoticons')"
                :aria-hidden="activeSettingsTab === 'emoticons' ? 'false' : 'true'"
                class="space-y-3 border-t border-[var(--rp-border-subtle)] pt-4"
            >
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
                                        <RpButton
                                            variant="ghost"
                                            class="text-xs"
                                            :disabled="emoticonBusy"
                                            @click="toggleEmoticon(e)"
                                        >
                                            {{ e.is_active ? 'Вимкнути' : 'Увімкнути' }}
                                        </RpButton>
                                        <RpButton
                                            variant="ghost"
                                            class="text-xs text-[var(--rp-error)]"
                                            :disabled="emoticonBusy"
                                            @click="deleteEmoticon(e)"
                                        >
                                            Видалити
                                        </RpButton>
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
                    <RpButton class="text-sm" :loading="emoticonBusy" :disabled="emoticonBusy" @click="submitNewEmoticon">
                        {{ emoticonBusy ? 'Збереження…' : 'Додати смайл' }}
                    </RpButton>
                </fieldset>
            </div>
        </div>
    </RpModal>
</template>

<script>
import RpModal from './RpModal.vue';
import RpCountryCombobox from './ui/RpCountryCombobox.vue';
import { formatChatImageMaxLabel } from '../utils/chatComposerImageUpload';
import { loadChatEmoticonsCatalog } from '../utils/chatEmoticons';

let titleSeq = 0;

export default {
    name: 'ChatSettingsModal',
    components: { RpModal, RpCountryCombobox },
    props: {
        open: { type: Boolean, default: false },
        rooms: { type: Array, default: () => [] },
        ensureSanctum: { type: Function, required: true },
    },
    data() {
        titleSeq += 1;

        return {
            titleId: `chat-settings-title-${titleSeq}`,
            activeSettingsTab: 'rooms',
            loading: false,
            saving: false,
            loadError: '',
            saveError: '',
            form: {
                message_edit_window_hours: 24,
                room_create_min_public_messages: 100,
                public_message_count_scope: 'all_public_rooms',
                message_count_room_id: null,
                slash_command_max_per_window: 45,
                slash_command_window_seconds: 60,
                mod_slash_default_mute_minutes: 30,
                mod_slash_default_kick_minutes: 60,
                silent_mode: false,
                sound_on_every_post: false,
                message_flood_enabled: false,
                message_flood_max_messages: 5,
                message_flood_window_seconds: 10,
                proxycheck_enabled: true,
                landing_settings: {
                    page_title: '',
                    tagline: '',
                    news_title: '',
                    news_body: '',
                    links: [
                        { label: '', url: '' },
                        { label: '', url: '' },
                        { label: '', url: '' },
                        { label: '', url: '' },
                    ],
                },
                registration_flags: {
                    registration_open: true,
                    min_age: null,
                    show_social_login_buttons: false,
                },
                transactional_mail_from_name: '',
                mail_template_overrides: {
                    password_reset: { subject: '', html_body: '', text_body: '' },
                    welcome_registered: { subject: '', html_body: '', text_body: '' },
                    account_security_notice: { subject: '', html_body: '', text_body: '' },
                },
                max_attachment_mb: 4,
            },
            mailTemplateSections: [
                {
                    id: 'password_reset',
                    title: 'Скидання пароля',
                    placeholders: '{{ app_name }}, {{ reset_url }}, {{ expire_minutes }}',
                },
                {
                    id: 'welcome_registered',
                    title: 'Ласкаво просимо (після реєстрації)',
                    placeholders: '{{ app_name }}, {{ user_name }}, {{ chat_url }}',
                },
                {
                    id: 'account_security_notice',
                    title: 'Сповіщення безпеки (шаблон)',
                    placeholders: '{{ app_name }}, {{ user_name }}, {{ headline }}, {{ body_line }}',
                },
            ],
            attachmentEffectiveBytesHint: null,
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
            botProfileLoading: false,
            botProfileSaving: false,
            botProfileError: '',
            botProfileMissing: false,
            botAvatarUrl: '',
            botAvatarUploading: false,
            botAvatarError: '',
            botForm: {
                user_name: '',
                profile: {
                    country: null,
                    about: '',
                },
            },
        };
    },
    computed: {
        settingsTabs() {
            return [
                { id: 'rooms', label: 'Кімнати' },
                { id: 'slash', label: 'Команди й модерація' },
                { id: 'media', label: 'Звуки та файли' },
                { id: 'landing', label: 'Вітальня' },
                { id: 'registration', label: 'Реєстрація' },
                { id: 'mail', label: 'Листи' },
                { id: 'system_bot', label: 'Руда панда' },
                { id: 'emoticons', label: 'Смайли' },
            ];
        },
        publicRooms() {
            const list = this.rooms || [];

            return list.filter((r) => Number(r.access) === 0);
        },
        attachmentEffectiveLabel() {
            const n = Number(this.attachmentEffectiveBytesHint);

            return Number.isFinite(n) && n > 0 ? formatChatImageMaxLabel(n) : '';
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
                this.activeSettingsTab = 'rooms';
                this.loadError = '';
                this.saveError = '';
                this.emoticonError = '';
                this.botProfileError = '';
                this.botAvatarError = '';
                this.load();
                this.loadEmoticonList();
            }
        },
    },
    methods: {
        tabElementId(id) {
            return `${this.titleId}-tab-${id}`;
        },
        tabPanelId(id) {
            return `${this.titleId}-panel-${id}`;
        },
        activateSettingsTab(id) {
            this.activeSettingsTab = id;
            this.$nextTick(() => {
                const refs = this.$refs.settingsTabButtons;
                const buttons = Array.isArray(refs) ? refs : refs ? [refs] : [];
                const idx = this.settingsTabs.findIndex((t) => t.id === id);
                if (idx >= 0 && buttons[idx]) {
                    buttons[idx].focus();
                }
            });
        },
        onSettingsTabKeydown(e) {
            if (e.target.getAttribute('role') !== 'tab') {
                return;
            }
            const navKeys = ['ArrowLeft', 'ArrowRight', 'Home', 'End'];
            if (!navKeys.includes(e.key)) {
                return;
            }
            e.preventDefault();
            const list = this.settingsTabs;
            let i = list.findIndex((t) => t.id === this.activeSettingsTab);
            if (i < 0) {
                i = 0;
            }
            if (e.key === 'Home') {
                i = 0;
            } else if (e.key === 'End') {
                i = list.length - 1;
            } else if (e.key === 'ArrowRight') {
                i = (i + 1) % list.length;
            } else if (e.key === 'ArrowLeft') {
                i = (i - 1 + list.length) % list.length;
            }
            const next = list[i];
            if (next) {
                this.activateSettingsTab(next.id);
            }
        },
        tabIdForValidationKey(key) {
            if (!key || typeof key !== 'string') {
                return 'rooms';
            }
            if (key.startsWith('landing_settings')) {
                return 'landing';
            }
            if (key.startsWith('registration_flags')) {
                return 'registration';
            }
            if (key === 'transactional_mail_from_name' || key.startsWith('mail_template_overrides')) {
                return 'mail';
            }
            if (
                key.startsWith('slash_command')
                || key.startsWith('mod_slash')
                || key.startsWith('message_flood')
                || key.startsWith('proxycheck')
            ) {
                return 'slash';
            }
            if (key === 'silent_mode' || key === 'sound_on_every_post' || key === 'max_attachment_bytes') {
                return 'media';
            }
            if (
                key === 'message_edit_window_hours' ||
                key === 'room_create_min_public_messages' ||
                key === 'public_message_count_scope' ||
                key === 'message_count_room_id'
            ) {
                return 'rooms';
            }

            return 'rooms';
        },
        focusFieldForValidationKey(key) {
            const idMap = {
                message_edit_window_hours: 'cs-edit-window-hours',
                room_create_min_public_messages: 'cs-n',
                public_message_count_scope: 'cs-scope',
                message_count_room_id: 'cs-room',
                slash_command_max_per_window: 'cs-slash-max',
                slash_command_window_seconds: 'cs-slash-window',
                message_flood_enabled: 'cs-flood-enabled',
                message_flood_max_messages: 'cs-flood-max',
                message_flood_window_seconds: 'cs-flood-window',
                proxycheck_enabled: 'cs-proxycheck-enabled',
                mod_slash_default_mute_minutes: 'cs-mod-mute',
                mod_slash_default_kick_minutes: 'cs-mod-kick',
                silent_mode: 'cs-silent-mode',
                sound_on_every_post: 'cs-sound-every-post',
                max_attachment_bytes: 'cs-max-att-mb',
                'landing_settings.page_title': 'cs-lp-title',
                'landing_settings.tagline': 'cs-lp-tag',
                'landing_settings.news_title': 'cs-lp-news-t',
                'landing_settings.news_body': 'cs-lp-news-b',
                'registration_flags.min_age': 'cs-reg-min-age',
            };
            let fieldId = idMap[key];
            if (!fieldId && key.startsWith('landing_settings.links.')) {
                const m = key.match(/^landing_settings\.links\.(\d+)\.(label|url)$/);
                if (m && m[2] === 'url') {
                    fieldId = `cs-lp-link-url-${m[1]}`;
                }
            }
            if (!fieldId && key.startsWith('landing_settings.')) {
                const sub = key.slice('landing_settings.'.length);
                fieldId = idMap[`landing_settings.${sub}`];
            }
            if (!fieldId && key.startsWith('registration_flags.')) {
                const sub = key.slice('registration_flags.'.length);
                fieldId = idMap[`registration_flags.${sub}`];
            }
            const el = fieldId ? document.getElementById(fieldId) : null;
            if (el && typeof el.focus === 'function') {
                el.focus();

                return;
            }
            const panel = document.getElementById(this.tabPanelId(this.tabIdForValidationKey(key)));
            if (panel && typeof panel.scrollIntoView === 'function') {
                panel.scrollIntoView({ block: 'nearest' });
            }
        },
        mailPreviewHtml(sectionId) {
            const raw = (this.form.mail_template_overrides[sectionId] || {}).html_body || '';
            const samples = {
                app_name: 'Чат Рудої Панди',
                reset_url: 'https://example.test/reset-password?token=demo&email=u%40example.com',
                expire_minutes: '60',
                user_name: 'DemoUser',
                chat_url: 'https://example.test/chat',
                headline: 'Перевірка безпеки',
                body_line: 'Тестовий рядок повідомлення.',
            };
            return this.replaceMailPlaceholders(String(raw), samples);
        },
        replaceMailPlaceholders(str, map) {
            return str.replace(/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/g, (full, k) =>
                map[k] != null ? String(map[k]) : full,
            );
        },
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
                const editH = Number(d.message_edit_window_hours);

                this.form = {
                    message_edit_window_hours:
                        Number.isFinite(editH) && editH >= 0 ? Math.min(8760, Math.floor(editH)) : 24,
                    room_create_min_public_messages: Number(d.room_create_min_public_messages) || 0,
                    public_message_count_scope:
                        d.public_message_count_scope === 'default_room_only'
                            ? 'default_room_only'
                            : 'all_public_rooms',
                    message_count_room_id: d.message_count_room_id != null ? Number(d.message_count_room_id) : null,
                    slash_command_max_per_window:
                        Number(d.slash_command_max_per_window) > 0 ? Number(d.slash_command_max_per_window) : 45,
                    slash_command_window_seconds:
                        Number(d.slash_command_window_seconds) >= 10
                            ? Number(d.slash_command_window_seconds)
                            : 60,
                    mod_slash_default_mute_minutes:
                        Number(d.mod_slash_default_mute_minutes) >= 1
                            ? Number(d.mod_slash_default_mute_minutes)
                            : 30,
                    mod_slash_default_kick_minutes:
                        Number(d.mod_slash_default_kick_minutes) >= 1
                            ? Number(d.mod_slash_default_kick_minutes)
                            : 60,
                    silent_mode: Boolean(d.silent_mode),
                    sound_on_every_post: Boolean(d.sound_on_every_post),
                    message_flood_enabled: Boolean(d.message_flood_enabled),
                    message_flood_max_messages:
                        Number(d.message_flood_max_messages) >= 1
                            ? Number(d.message_flood_max_messages)
                            : 5,
                    message_flood_window_seconds:
                        Number(d.message_flood_window_seconds) >= 1
                            ? Number(d.message_flood_window_seconds)
                            : 10,
                    proxycheck_enabled: d.proxycheck_enabled === false ? false : true,
                };
                const ls = d.landing_settings && typeof d.landing_settings === 'object' ? d.landing_settings : {};
                const rawLinks = Array.isArray(ls.links) ? ls.links : [];
                const links = rawLinks.map((l) => ({
                    label: l && l.label != null ? String(l.label) : '',
                    url: l && l.url != null ? String(l.url) : '',
                }));
                while (links.length < 4) {
                    links.push({ label: '', url: '' });
                }
                this.form.landing_settings = {
                    page_title: ls.page_title != null ? String(ls.page_title) : '',
                    tagline: ls.tagline != null ? String(ls.tagline) : '',
                    news_title: ls.news_title != null ? String(ls.news_title) : '',
                    news_body: ls.news_body != null ? String(ls.news_body) : '',
                    links: links.slice(0, 8),
                };
                const rf = d.registration_flags && typeof d.registration_flags === 'object' ? d.registration_flags : {};
                const minA = rf.min_age;
                this.form.registration_flags = {
                    registration_open: rf.registration_open !== false,
                    min_age: minA === null || minA === undefined || minA === '' ? null : Number(minA),
                    show_social_login_buttons: Boolean(rf.show_social_login_buttons),
                };
                this.form.transactional_mail_from_name =
                    d.transactional_mail_from_name != null ? String(d.transactional_mail_from_name) : '';
                const mo = d.mail_template_overrides && typeof d.mail_template_overrides === 'object' ? d.mail_template_overrides : {};
                const mergeBlock = (id) => ({
                    subject: '',
                    html_body: '',
                    text_body: '',
                    ...(mo[id] && typeof mo[id] === 'object' ? mo[id] : {}),
                });
                this.form.mail_template_overrides = {
                    password_reset: mergeBlock('password_reset'),
                    welcome_registered: mergeBlock('welcome_registered'),
                    account_security_notice: mergeBlock('account_security_notice'),
                };
                const attBytes = Number(d.max_attachment_bytes);
                if (Number.isFinite(attBytes) && attBytes >= 1024) {
                    const mbRaw = attBytes / (1024 * 1024);
                    this.form.max_attachment_mb = Math.max(0.01, Math.round(mbRaw * 100) / 100);
                } else {
                    this.form.max_attachment_mb = 4;
                }
                const effHint = Number(d.max_chat_image_upload_bytes);
                this.attachmentEffectiveBytesHint =
                    Number.isFinite(effHint) && effHint > 0 ? effHint : null;
            } catch {
                this.loadError = 'Не вдалося завантажити налаштування.';
            } finally {
                this.loading = false;
            }
            await this.loadBotProfile();
        },
        onBotCountryInput(v) {
            const s = v != null ? String(v).trim().toUpperCase() : '';

            this.botForm.profile.country = s.length === 2 ? s : null;
        },
        async loadBotProfile() {
            this.botProfileLoading = true;
            this.botProfileError = '';
            this.botProfileMissing = false;
            try {
                await this.ensureSanctum();
                const { data } = await window.axios.get('/api/v1/chat/system-bot/profile');
                const d = data && data.data;
                if (!d) {
                    this.botProfileMissing = true;

                    return;
                }
                this.botForm.user_name = d.user_name != null ? String(d.user_name) : '';
                const p = d.profile && typeof d.profile === 'object' ? d.profile : {};
                this.botForm.profile = {
                    country: p.country != null && String(p.country).trim() !== '' ? String(p.country).trim().toUpperCase() : null,
                    about: p.about != null ? String(p.about) : '',
                };
                this.botAvatarUrl = d.avatar_url != null ? String(d.avatar_url) : '';
            } catch (e) {
                const st = e.response && e.response.status;
                if (st === 404) {
                    this.botProfileMissing = true;
                } else if (st === 403) {
                    this.botProfileError = 'Недостатньо прав (потрібен адміністратор чату).';
                } else {
                    this.botProfileError = 'Не вдалося завантажити профіль бота.';
                }
            } finally {
                this.botProfileLoading = false;
            }
        },
        onBotAvatarPickClick() {
            const el = this.$refs.botAvatarInput;
            if (el && typeof el.click === 'function') {
                el.click();
            }
        },
        async onBotAvatarFileSelected(e) {
            const input = e.target;
            const file = input.files && input.files[0];
            if (!file) {
                return;
            }
            this.botAvatarError = '';
            this.botAvatarUploading = true;
            try {
                await this.ensureSanctum();
                const form = new FormData();
                form.append('image', file);
                const { data } = await window.axios.post('/api/v1/chat/system-bot/avatar', form, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                const d = data && data.data;
                if (d && d.avatar_url != null) {
                    this.botAvatarUrl = String(d.avatar_url);
                } else {
                    await this.loadBotProfile();
                }
                this.$emit('saved');
            } catch (err) {
                const st = err.response && err.response.status;
                if (st === 403) {
                    this.botAvatarError = 'Недостатньо прав (потрібен адміністратор чату).';
                } else {
                    const payload = err.response && err.response.data;
                    const msg =
                        (payload && payload.message) ||
                        (err.response && err.response.status === 422 ? 'Перевірте формат або розмір файлу.' : '');
                    this.botAvatarError = msg || 'Не вдалося оновити аватарку.';
                }
            } finally {
                this.botAvatarUploading = false;
                input.value = '';
            }
        },
        async saveBotProfile() {
            this.botProfileSaving = true;
            this.botProfileError = '';
            try {
                await this.ensureSanctum();
                const name = (this.botForm.user_name || '').trim();
                const body = {
                    user_name: name,
                    profile: {
                        country: this.botForm.profile.country,
                        about: (this.botForm.profile.about || '').trim() || null,
                    },
                };
                await window.axios.patch('/api/v1/chat/system-bot/profile', body);
                this.$emit('saved');
                await this.loadBotProfile();
            } catch (e) {
                const st = e.response && e.response.status;
                const payload = e.response && e.response.data;
                const errs = payload && payload.errors;
                if (st === 403) {
                    this.botProfileError = 'Недостатньо прав (потрібен адміністратор чату).';
                } else if (errs && typeof errs === 'object' && !Array.isArray(errs)) {
                    const keys = Object.keys(errs);
                    const firstKey = keys.length ? keys[0] : '';
                    const msgs = firstKey ? errs[firstKey] : null;
                    const firstMsg =
                        Array.isArray(msgs) && msgs.length && typeof msgs[0] === 'string' ? msgs[0] : '';
                    this.botProfileError = firstMsg || (payload && payload.message) || 'Не вдалося зберегти.';
                } else {
                    this.botProfileError = (payload && payload.message) || 'Не вдалося зберегти.';
                }
            } finally {
                this.botProfileSaving = false;
            }
        },
        async save() {
            this.saving = true;
            this.saveError = '';
            try {
                await this.ensureSanctum();
                const body = {
                    message_edit_window_hours: this.form.message_edit_window_hours,
                    room_create_min_public_messages: this.form.room_create_min_public_messages,
                    public_message_count_scope: this.form.public_message_count_scope,
                    slash_command_max_per_window: this.form.slash_command_max_per_window,
                    slash_command_window_seconds: this.form.slash_command_window_seconds,
                    mod_slash_default_mute_minutes: this.form.mod_slash_default_mute_minutes,
                    mod_slash_default_kick_minutes: this.form.mod_slash_default_kick_minutes,
                    silent_mode: Boolean(this.form.silent_mode),
                    sound_on_every_post: Boolean(this.form.sound_on_every_post),
                    message_flood_enabled: Boolean(this.form.message_flood_enabled),
                    message_flood_max_messages: Number(this.form.message_flood_max_messages),
                    message_flood_window_seconds: Number(this.form.message_flood_window_seconds),
                    proxycheck_enabled: Boolean(this.form.proxycheck_enabled),
                    landing_settings: {
                        page_title: (this.form.landing_settings.page_title || '').trim() || null,
                        tagline: (this.form.landing_settings.tagline || '').trim() || null,
                        news_title: (this.form.landing_settings.news_title || '').trim(),
                        news_body: (this.form.landing_settings.news_body || '').trim(),
                        links: (this.form.landing_settings.links || [])
                            .map((l) => ({
                                label: (l.label || '').trim(),
                                url: (l.url || '').trim(),
                            }))
                            .filter((l) => l.label || l.url),
                    },
                    registration_flags: {
                        registration_open: Boolean(this.form.registration_flags.registration_open),
                        show_social_login_buttons: Boolean(this.form.registration_flags.show_social_login_buttons),
                        min_age:
                            this.form.registration_flags.min_age === '' ||
                            this.form.registration_flags.min_age === null ||
                            Number.isNaN(Number(this.form.registration_flags.min_age))
                                ? null
                                : Number(this.form.registration_flags.min_age),
                    },
                    transactional_mail_from_name: (this.form.transactional_mail_from_name || '').trim() || null,
                    mail_template_overrides: this.form.mail_template_overrides,
                };
                if (this.form.public_message_count_scope === 'default_room_only') {
                    body.message_count_room_id = this.form.message_count_room_id;
                } else {
                    body.message_count_room_id = null;
                }
                const floodMax = Number(this.form.message_flood_max_messages);
                const floodWin = Number(this.form.message_flood_window_seconds);
                if (
                    !Number.isFinite(floodMax)
                    || floodMax < 1
                    || floodMax > 65535
                    || !Number.isFinite(floodWin)
                    || floodWin < 1
                    || floodWin > 86400
                ) {
                    this.saveError = 'Вкажіть коректні параметри антифлуду (N і T).';
                    this.activeSettingsTab = 'slash';
                    this.saving = false;
                    this.$nextTick(() => {
                        const el = document.getElementById('cs-flood-max');
                        if (el && typeof el.focus === 'function') {
                            el.focus();
                        }
                    });

                    return;
                }
                const mb = Number(this.form.max_attachment_mb);
                if (!Number.isFinite(mb) || mb <= 0) {
                    this.saveError = 'Вкажіть коректний ліміт розміру файлу (МБ).';
                    this.activeSettingsTab = 'media';
                    this.saving = false;
                    this.$nextTick(() => {
                        const el = document.getElementById('cs-max-att-mb');
                        if (el && typeof el.focus === 'function') {
                            el.focus();
                        }
                    });

                    return;
                }
                body.max_attachment_bytes = Math.min(
                    100 * 1024 * 1024,
                    Math.max(1024, Math.round(mb * 1024 * 1024)),
                );
                await window.axios.patch('/api/v1/chat/settings', body);
                this.$emit('saved');
                this.close();
            } catch (e) {
                const st = e.response && e.response.status;
                const payload = e.response && e.response.data;
                const errs = payload && payload.errors;
                if (st === 403) {
                    this.saveError = 'Недостатньо прав (потрібен адміністратор чату).';
                } else if (errs && typeof errs === 'object' && !Array.isArray(errs)) {
                    const keys = Object.keys(errs);
                    const firstKey = keys.length ? keys[0] : '';
                    const msgs = firstKey ? errs[firstKey] : null;
                    const firstMsg =
                        Array.isArray(msgs) && msgs.length && typeof msgs[0] === 'string' ? msgs[0] : '';
                    this.saveError = firstMsg || (payload && payload.message) || 'Не вдалося зберегти.';
                    if (firstKey) {
                        this.activeSettingsTab = this.tabIdForValidationKey(firstKey);
                        this.$nextTick(() => this.focusFieldForValidationKey(firstKey));
                    }
                } else {
                    this.saveError = (payload && payload.message) || 'Не вдалося зберегти.';
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
