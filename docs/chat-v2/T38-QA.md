# T38 — QA: декомпозиція `ChatRoom.vue`

**Задача:** інкрементальне винесення частин екрану чату без зміни поведінки.

### Слайс 1

Рядок стрічки → `ChatFeedMessageRow.vue`.

### Слайс 2 (додатково)

- **Хедер** → `ChatRoomHeader.vue` (refs `mobilePanelToggle` / `desktopPanelToggle` лишаються для фокусу після вибору кімнати на вузькому екрані).
- **Список повідомлень** (прокрутка + порожній стан) → `ChatFeedMessageList.vue` з публічним методом `scrollToBottom()` замість `ref` на `<ul>` у батькові.

### Слайс 3

- **Композер** (форма, тулбар, палітри, textarea, зображення) → `ChatRoomComposer.vue`; стан тексту/стилю/прев’ю картинки всередині; батько викликає `getSendPayload()` / `resetAfterSend()` у `sendMessage`; палітри та `buildStylePayloadForApi` → `chatMessageStyle.js`.

### Слайс 4

- **Сайдбар** (`aside` 320px, таби, люди/друзі/приват/кімнати/ігнор) → `ChatRoomSidebar.vue`; refs `panelCloseBtnMobile` / `panelCloseBtnDesktop` для `focusPanelCloseButton` у батькові через `ref="chatRoomSidebar"`; клавіатура табів і `presenceRowKey` всередині дитини; `.sync` для `peerLookupName` та `friendsSubTab`.

## Вердикт

- [x] **PASS** (після `npm run build`)

## Докази

| Перевірка | Результат |
|-----------|-----------|
| `npm run build` (у каталозі `backend/`) | без помилок |

## Ручний чекліст (рекомендовано перед релізом)

1. Увійти в чат, надіслати повідомлення; переконатися, що стрічка й чергування рядків як раніше.
2. Клік по **чужому** ніку — у композер додається згадка (`нік > ` за поточною логікою).
3. Клік по **чужій** аватарці — у композер додається префікс інлайн-привату (`/msg`).
4. Повідомлення з **зображенням** — прев’ю як раніше.
5. **Інлайн-приват** (`inline_private`) — клас рядка `rp-chat-feed-row--inline-private` зберігається.
6. **Гість** (якщо доступно): нік без кнопки, аватарка без кнопки привату.
7. **Хедер:** вихід, архів, бургер/панель, перемикач теми, бейдж degraded WS — як раніше.
8. **Вузький екран:** вибір іншої кімнати в сайдбарі закриває панель і повертає фокус на кнопку меню в хедері.
9. **Композер:** B/I/U, фон/колір тексту, Enter / Shift+Enter, вкладення фото, тулбар «Архів» / «Вийти».
10. **Сайдбар:** усі вкладки, меню бейджів, приват за ніком, зміна кімнати, друзі/ігнор; off-canvas + фокус на кнопку закриття.

## Артефакти

- `backend/resources/js/components/chat/ChatFeedMessageRow.vue`
- `backend/resources/js/components/chat/ChatFeedMessageList.vue`
- `backend/resources/js/components/chat/ChatRoomHeader.vue`
- `backend/resources/js/components/chat/ChatRoomComposer.vue`
- `backend/resources/js/utils/chatMessageStyle.js` — `nickColorStyleForPost()`, `COMPOSER_*_PALETTE`, `buildStylePayloadForApi()`
