# Chat v2 — специфікація проєкту (індекс)

**Призначення:** канонічне джерело вимог для фази Planning (Agents Orchestrator). Детальний технічний план: [.cursor/plans або копія в репо](#копія-плану).

## Розташування коду

Робочий tree додатку: **`backend/`** (Laravel, Vite, `composer.json`, `package.json`). Документація вимог лишається в `docs/` на корені репозиторію.

## Продукт

Функціональний наступник [board.te.ua](https://www.board.te.ua/): публічний чат кімнатами, приват, друзі, архів, профіль. Бекенд — **Laravel** (остання стабільна гілка), фронт — **Vue 2.7** SPA, БД — **MySQL** за логікою наведеної схеми.

## Документація вимог (цитувати при декомпозиції задач)

| Документ | Зміст |
|----------|--------|
| [docs/chat-v2/openapi.yaml](../docs/chat-v2/openapi.yaml) | OpenAPI 3.0 — Auth + Chat REST + примітки WS/SPA (узгоджувати з кодом при змінах API) |
| [docs/chat-v2/T06-QA.md](../docs/chat-v2/T06-QA.md) | Vue чат `/chat`, Echo, poll fallback |
| [docs/chat-v2/T07-QA.md](../docs/chat-v2/T07-QA.md) | Панель 320px, вкладки, off-canvas |
| [docs/chat-v2/T08-QA.md](../docs/chat-v2/T08-QA.md) | Приват, друзі, ignore, `/msg` |
| [docs/chat-v2/T09-QA.md](../docs/chat-v2/T09-QA.md) | Архів чату, пагінація, пошук |
| [docs/board-te-ua/SITE-STRUCTURE.md](../docs/board-te-ua/SITE-STRUCTURE.md) | Сценарії, екрани, команди, архів |
| [docs/board-te-ua/CHAT-PANEL-SIDEBAR.md](../docs/board-te-ua/CHAT-PANEL-SIDEBAR.md) | Сайдбар 320px, вкладки, панелі |
| [docs/board-te-ua/CHAT-MAIN-INPUT.md](../docs/board-te-ua/CHAT-MAIN-INPUT.md) | Стрічка, ввід, смайли, POST |
| [docs/board-te-ua/PRIVATE-MESSAGES.md](../docs/board-te-ua/PRIVATE-MESSAGES.md) | Приват, `/msg`, private_panel |
| [docs/board-te-ua/DATABASE-SCHEMA.md](../docs/board-te-ua/DATABASE-SCHEMA.md) | Таблиці, індекси, PII, оптимізація |

## Обмеження та нефункціональні вимоги

- Авторизація каналів WebSocket обов’язкова; HTTP — джерело правди для прийняття повідомлення (`post_id`).
- WCAG AA для ключових потоків; design tokens; `prefers-reduced-motion`.
- Дамп [org100h.sql](../docs/board-te-ua/org100h.sql) не комітити; PII лише в ізольованих середовищах.
- Не розширювати scope (Facebook, AdSense) без явного запиту.

## Копія плану

Майстер-план з архітектурою, безпекою та UI: узгоджуйте з ведучим оркестратора — файл у Cursor plans `chat_v2_laravel_vue_*.plan.md` або експорт у `docs/chat-v2/` за потреби.
