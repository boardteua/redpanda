# T88 — QA evidence (кнопки / submit у SPA)

## Канон (для PR)

Primary CTA у формах і модалках — компонент **`RpButton`** з `variant="primary"` (дефолт) або еквівалентні класи `rp-focusable rp-btn rp-btn-primary` лише там, де потрібен «голий» `<button>` (наприклад, таби, іконки закриття). Приклад: `<RpButton native-type="submit" class="w-full text-sm" :loading="busy">Зберегти</RpButton>`. Не використовувати одноразовий `bg-[var(--rp-chat-sidebar-link)]` для головної дії форми.

## Автоматичні перевірки

- `npm run build` (у `backend/`) — **PASS** (2026-03-23).

## `rg`: патерн `bg-[var(--rp-chat-sidebar-link)]` на кнопках

Команда з кореня репозиторію:

```bash
rg 'bg-\[var\(--rp-chat-sidebar-link\)\]' backend/resources/js --glob '*.vue'
```

Очікувано: **0 збігів** (після T88 primary CTA в `AddRoomModal` переведено на `RpButton`).

## Винятки (узгоджені)

- **`ChatRoomComposer.vue`** — кнопка надсилання з `type="submit"` і класом **`rp-chat-send-primary`** (іконка в композері, не текстовий primary CTA модалки). Залишено як спеціалізований chrome чату.
- **`ChatRoomSidebar.vue`** — кнопка «Додати кімнату» зі стилями сайдбару (`--rp-chat-sidebar-*`), не глобальний primary з `--rp-primary`; узгоджено з контекстом панелі.

## Ручний чек (світла тема)

- Модал **створення кімнати** (`AddRoomModal`): primary submit — узгоджено з каноном; **disabled** — видима непрозорість, `cursor: not-allowed` (`.rp-btn-primary:disabled`).
- Модал **налаштувань чату** (`ChatSettingsModal`): «Зберегти» / «Додати смайл» — `RpButton`.
- **Staff** — `StaffUsersView`: primary/secondary/ghost через `RpButton`.

Скріншоти: за потреби додати в PR порівняння AddRoomModal + фрагмент ChatSettingsModal (оператор).
