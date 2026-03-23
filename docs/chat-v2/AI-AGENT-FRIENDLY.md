# Redpanda Chat v2 — контекст для інтеграторів і LLM

Цей документ описує, як **без виконання JavaScript SPA** зрозміти продукт, автентифікацію та основні HTTP-флоу. Він узгоджений із [`project-specs/chat-v2-setup.md`](../project-specs/chat-v2-setup.md), [`docs/chat-v2/openapi.yaml`](openapi.yaml) та оглядами legacy у [`docs/board-te-ua/`](../board-te-ua/).

## Політика безпеки та приватності

- **Не** включайте до публічних відповідей **секрети** (`.env`, ключі API, паролі, токени сесій).
- **Не** публікуйте **PII** користувачів (email, IP, повні профілі), якщо це не ваш власний тестовий акаунт у ізольованому середовищі.
- **Staff / модерація:** ендпоінти під `can:moderate` та `can:chat-admin` **не** є публічним контекстом для загального LLM-індексу; описуйте їх лише на рівні «існують і вимагають ролі», без URL-ів з реальними id.
- Публічні машинні огляди: **`GET /llms.txt`** (шаблон у репозиторії: `backend/resources/content/llms.txt`; підстановка `APP_URL` на сервері) та **`GET /docs/chat-v2/AI-AGENT-FRIENDLY.md`** через Laravel-маршрут (див. `routes/web.php`).

## Продукт (коротко)

- **Наступник** за сценаріями [board.te.ua](https://www.board.te.ua/): кімнатний чат, приватні повідомлення, друзі, архів, профіль.
- **Стек:** Laravel (додаток у `backend/`), Vue 2.7 SPA, MySQL, Redis/Reverb для real-time.
- **Джерело правди для повідомлень:** HTTP API (ідемпотентний POST, `post_id`); WebSocket — доставка та presence.

## Що доступно без JS

| Ресурс | Призначення |
|--------|-------------|
| `GET /llms.txt` | Markdown-огляд + посилання |
| `GET /docs/openapi.yaml` | OpenAPI 3 (якщо монорепо доступний з `backend/`) |
| `GET /docs/chat-v2/AI-AGENT-FRIENDLY.md` | Цей документ |
| `GET /docs/project-specs/chat-v2-setup.md` | Індекс специфікації |
| `GET /api/v1/landing` | Публічні поля вітальні (без секретів) |
| `GET /up`, `GET /health/ready` | Liveness / readiness |
| `POST /api/v1/auth/*` | Реєстрація, логін, гість (див. OpenAPI) |

**Що вимагає JS у браузері:** інтерактивний чат (`/chat`), Echo/Reverb, повна навігація SPA. Для агентів компенсуйте викликами **REST** за OpenAPI та текстовими описами подій у `openapi.yaml` → `info.description`.

## Авторизація (Sanctum SPA + гість)

1. Для браузерного клієнта з того ж origin: `GET /sanctum/csrf-cookie` з `credentials: include`.
2. Змінюючі запити: заголовок `X-XSRF-TOKEN` (значення з cookie `XSRF-TOKEN`, URL-decoded) + cookie сесії.
3. **Гість:** окремий потік `POST /api/v1/auth/guest` — залишається на Laravel-сесії, **без** сторонніх IdP (узгоджено з дорожньою картою Auth0 у окремих задачах).
4. Деталі та коди помилок — у [openapi.yaml](openapi.yaml) і QA-нотатках T02/T03.

## Чекліст розділів для `/llms.txt` (llms.txt proposal)

- [x] `# Title` — назва продукту
- [x] `> summary` — короткий зміст
- [x] `## Docs` — посилання на OpenAPI, специфікацію, цей файл
- [x] `## Public HTTP` — анонімні GET
- [x] `## Auth` — Sanctum / гість (без секретів)
- [x] `## Examples` — типові префікси API
- [x] `## Optional` — клон репозиторію, обмеження

## Сценарії (логічні кроки, не скрипти)

**Перегляд вітальні (публічно):** `GET /api/v1/landing` → відобразити тексти/прапорці, дозволені відповіддю.

**Реєстрація → чат:** `POST .../auth/register` → `POST .../auth/login` або одразу логін → `GET .../rooms` → `GET .../rooms/{id}/messages` → `POST .../rooms/{id}/messages` (з CSRF для SPA).

**Гість:** `POST .../auth/guest` → далі обмежений набір дій (див. ролі в OpenAPI / T21).

## Що LLM «не бачить» без JS

- Реальний рендер компонентів Vue, порядок завантаження Vite-модулів.
- Підписки WebSocket без спочатку отриманої сесії та `POST /broadcasting/auth`.
- Внутрішній стан Redux/Vuex (якщо з’явиться) — орієнтуйтесь на **відповіді API**.

**Компенсація:** OpenAPI, цей файл, `/llms.txt`, markdown/board-te-ua-доки в репозиторії.

## `robots.txt` та AI-краулери

Поведінка пошукових і AI-ботів **не** зафіксована в цій задачі як обов’язкова. За потреби продукт додає правила окремо (узгодити з юридичною політикою).

## Де шукати деталі в репозиторії

- HTTP контракт: `docs/chat-v2/openapi.yaml`
- Сценарії legacy UX: `docs/board-te-ua/SITE-STRUCTURE.md`, `CHAT-*`, `PRIVATE-MESSAGES.md`, `DATABASE-SCHEMA.md`
- Операційні нотатки: `docs/chat-v2/T11-RUNBOOK.md`, `T11-QA.md`

Оновлюйте цей файл при суттєвих змінах публічних флоу або політики для агентів.
