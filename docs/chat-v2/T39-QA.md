# T39 — Універсальний RpModal + рефакторинг модалок (QA)

**Задача:** канонічний overlay-діалог (`RpModal.vue`) з `modalFocusTrap` (capture keydown, Tab-цикл, Escape, повернення фокусу); рефакторинг перелічених SFC без зміни публічних props/events для батьків.

## Компонент `RpModal`

- **Пропси:** `open`, `zIndex` (дефолт 75), `size` (`sm|md|lg|xl` → `max-w-*`), `maxHeightClass`, `variant` (`framed` | `card`), `title`, `closable`, `closeOnBackdrop`, `scrollBody`, `ariaLabelledby`, `ariaDescribedby`, `ariaBusy`, `panelClass`, `initialFocus` (`panel` | `first`).
- **Слоти:** `header` (framed), default (тіло), `footer` (framed і card).
- **Фокус:** за замовчуванням на панелі (`tabindex="-1"`); елемент з `data-rp-initial-focus` має пріоритет; інакше `initialFocus="first"` — перший фокусований з `getModalFocusables`.

## Рефакторинг під `RpModal`

| Компонент | Примітка |
|-----------|----------|
| `SimpleStubModal.vue` | `variant="card"` |
| `ConfirmDialogModal.vue` | `variant="card"`, footer з кнопками, `data-rp-initial-focus` на «Скасувати» |
| `CommandsHelpModal.vue` | framed, slot `header`, `footer`, `scrollBody` |
| `ChatMyImagesModal.vue` | `z-index` 78, framed, `scrollBody` false, внутрішній scroll |
| `ChatEmojiModal.vue` | як вище, пошук з `data-rp-initial-focus` |
| `UserInfoModal.vue` | framed, slot `header`, повний focus trap (раніше лише Escape) |
| `UserProfileModal.vue` | framed, `open` = `open && user && !user.guest` |
| `PrivateChatPanel.vue` | **виняток** — бічна панель layout, не overlay-діалог; без змін |

## Автоматичні докази

```bash
cd backend && npm run build
```

- **Вердикт:** PASS (на момент закриття T39).
- `php artisan test` — без змін у PHP; за бажанням повний прогін у CI.

## Ручний чекліст (браузер)

Для кожної модалки з таблиці: відкрити з чату → **Tab** / **Shift+Tab** лише всередині → **Escape** закриває → після закриття фокус на тригері → клік по підкладці закриває (де `closeOnBackdrop` увімкнено). Перевірити смайли (фокус у полі пошуку) та підтвердження видалення (фокус на «Скасувати»).
