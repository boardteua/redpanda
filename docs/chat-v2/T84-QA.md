# T84 — Vue декомпозиція композера (QA)

**Вердикт:** PASS (локальна збірка + тести)

## Команди

| Критерій | Результат |
|----------|-----------|
| `npm run build` (у `backend/`) | PASS |
| `php artisan test` | PASS — 285 tests |

## Регресія (ручна, за чеклістом T84)

- Логін → чат → композер: тулбар форматування, палітри bg/fg (клік поза панеллю закриває палітру через `ref` + `$el.contains`), архів, вихід, смайли, textarea, надсилання.
- Сайдбар: не змінювався в цьому коміті.
- Staff: не змінювався в цьому коміті.

## Зміни в структурі

- Нові SFC: `components/chat/composer/ChatRoomComposerToolbar.vue` (та інші підкомпоненти композера), `room/ChatRoomMainColumn.vue`, `sidebar/ChatSidebarTabBars.vue`, `room/ChatRoomModals.vue`.
- Структура: `chat/feed|composer|sidebar|room/` — див. `docs/chat-v2/T84-vue-decomposition.md`.
- Інвентаризація та наступні кандидати: `docs/chat-v2/T84-vue-decomposition.md`.
