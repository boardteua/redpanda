# T81 — QA evidence (a11y / WCAG AA: staff, архів, вітальня, модалки)

## Інвентаризація екранів

| Маршрут / компонент | Статус | Примітки |
|---------------------|--------|----------|
| `/` `AuthWelcome` | pass | `main#main-content`, вкладки входу, підписи полів, `rp-focusable`, банери помилок `role="alert"` |
| `/archive` `ArchiveChat` | pass | Заголовки таблиці `scope="col"`, фільтри з `<label for>`, пагінація з `aria-label`, `RpBanner` для помилки завантаження |
| `/chat/admin` `AdminHubView` | pass | Картки-хаб як посилання з фокусом, `aria-describedby` для списку |
| `/chat/staff-users` `StaffUsersView` | pass після правок | Рядки таблиці — клавіатура (Enter/Пробіл), `aria-label`, стовпець вибору з `aria-label` на чекбоксах |
| `/chat/staff-stop-words` `StaffStopWordsView` | pass після правок | Заголовок колонки дій (sr-only), колір кнопки «Видалити» через `--rp-error`, рядки з клавіатурою |
| `/chat/staff-flagged` `StaffFlaggedMessagesView` | pass після правок | Заголовок колонки дій (sr-only), статусні повідомлення з `aria-live="polite"` |
| `RpModal` + підтвердження / чат-модалки | pass | `role="dialog"`, `aria-modal`, заголовки, пастка фокусу в `modalFocusTrap.js` |
| Skip link (усі SPA з `spa.blade.php`) | pass | Посилання «Перейти до основного вмісту» → `#main-content` |

## Інструменти перевірки

- Ручний прохід: клавіатура (Tab / Shift+Tab / Enter / Пробіл / Escape у модалках), перевірка видимого `focus-visible` на кнопках і полях.
- За бажанням у dev: розширення **axe DevTools** (не блокер для merge згідно з задачею).

## Автоматичні перевірки

```bash
cd backend && npm run build
```

Очікування: збірка Vite без помилок.

## Ручний сценарій (критичний флоу)

1. Відкрити `/`, Tab — з’являється skip link, Enter — фокус на `#main-content`.
2. Увійти (логін або гість) лише з клавіатури → перехід у чат без миші.
3. За наявності прав: відкрити staff-сторінку / архів, пройти таблицю та фільтри, переконатися, що фокус видно на посиланнях і кнопках.

## Вердикт

PASS після успішного `npm run build` і ручного проходження сценарію вище.
