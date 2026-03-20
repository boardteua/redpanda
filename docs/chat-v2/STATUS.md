# Chat v2 — стан оркестрації

Оновлюйте після сесій (див. [AGENT-ORCHESTRATION.md](AGENT-ORCHESTRATION.md)).

| Поле | Значення |
|------|-----------|
| **Фаза** | Integration (обов’язкові T01–T12 + T14 закриті; T13 опційно) |
| **Поточна задача** | T13 (ETL) — опційно; інакше релізний hardening поза task list |
| **Спроба Dev (остання)** | T14: 1/3 |
| **Останній QA** | T14: **PASS** (2026-03-20, автотести + ручний чекліст у T14-QA) |
| **Блокери** | — |

## T14 — QA (доказ)

- `cd backend && php artisan test` — OK (`IntegrationFlowApiTest`, повний suite **59** тестів).
- `cd backend && npm run build` — OK.
- Чекліст і вердикт: [T14-QA.md](T14-QA.md).

## T14 — Інтеграція (підсумок)

| Елемент | Значення |
|---------|----------|
| API smoke | `IntegrationFlowApiTest` — кімнати, повідомлення, архів, приват/друзі/ігнор; гість у публічній кімнаті |
| SPA shell | `SpaShellTest` — `/`, `/chat`, `/archive` |
| Ручна перевірка | Послідовність у T14-QA (Echo, сайдбар, приват) |

## T12 — QA (доказ)

- `cd backend && php artisan test` — OK (`ModerationApiTest`, повний suite).
- `cd backend && npm run build` — OK.
- Чекліст: [T12-QA.md](T12-QA.md).

## T12 — Модерація MVP (підсумок)

| Елемент | Значення |
|---------|----------|
| Бан IP | `banned_ips` + middleware на `api/v1/*` |
| Фільтр | `filter_words` + заміна в публічному / приватному тексті |
| Mute / kick | `users.mute_until`, `kick_until` (unix); блок відправки |
| API | `/api/v1/mod/*`, `can:moderate` (`user_rank` ≥ 1), throttle `mod-actions` |

## T11 — QA (доказ)

- `cd backend && php artisan test` — OK (`HealthEndpointTest`, повний suite).
- `cd backend && npm run build` — OK.
- Чекліст: [T11-QA.md](T11-QA.md); runbook: [T11-RUNBOOK.md](T11-RUNBOOK.md).

## T11 — Спостережуваність (підсумок)

| Елемент | Значення |
|---------|----------|
| Liveness | `GET /up` (Laravel) |
| Readiness | `GET /health/ready` — JSON, опційно Redis за `HEALTH_CHECK_REDIS` / драйвери |
| Логи | Канал `structured` (JSON); middleware + `Authenticated` → `request_id`, `user_id` |

## T10 — QA (доказ)

- `cd backend && php artisan test` — OK (`ChatImageApiTest`, решта suite за CI).
- `cd backend && npm run build` — OK.
- Чекліст: [T10-QA.md](T10-QA.md).

## T10 — Медіа (підсумок)

| Елемент | Значення |
|---------|----------|
| API | `POST /api/v1/images`, `GET /api/v1/images/{id}/file`; `POST .../messages` з `image_id`; диск `storage/app/chat-images` |
| Політика | `ImagePolicy::view` — власник або повідомлення в доступній кімнаті |
| SPA | `ChatRoom.vue` upload + прев’ю; `ArchiveChat.vue` прев’ю в таблиці |

## T09 — QA (доказ)

- `cd backend && php artisan test` — OK (`ChatArchiveApiTest`, `SpaShellTest`).
- `cd backend && npm run build` — OK.
- Чекліст: [T09-QA.md](T09-QA.md).

## T09 — Архів (підсумок)

| Елемент | Значення |
|---------|----------|
| API | `GET /api/v1/archive/messages` — offset-пагінація, `q`, `room`; throttle `archive-read` |
| SPA | `/archive` → `ArchiveChat.vue`; з чату посилання з `?room=`; `/?history=1` → архів після входу |

## T08 — QA (доказ)

- `cd backend && php artisan test` — OK (`PrivateMessageApiTest`, `FriendApiTest`, `IgnoreApiTest`).
- `cd backend && npm run build` — OK.
- Чекліст: [T08-QA.md](T08-QA.md).

## T08 — Приват / друзі / ігнор (підсумок)

| Елемент | Значення |
|---------|----------|
| API | `private/*`, `users/lookup`, `friends*`, `ignores*` |
| WS | `.PrivateMessagePosted` → `private-user.{recipientId}` |
| UI | `PrivateChatPanel.vue`, оновлені вкладки сайдбару; `/msg нік [текст]` у головному композері |

## T07 — QA (доказ)

- `cd backend && php artisan test` — OK.
- `cd backend && npm run build` — OK.
- Чекліст: [T07-QA.md](T07-QA.md).

## T07 — Панель чату (підсумок)

| Елемент | Значення |
|---------|----------|
| Макет | `ChatRoom.vue`: колонка чату + aside **320px**; `max-md` off-canvas + backdrop |
| Вкладки | Люди (себе + нотатка про presence), Друзі (підвкладки), Приват / Ігнор — тексти як у board.te docs |
| Кімнати | Список у панелі; вибір кімнати = той самий flow що й раніше |

## T06 — QA (доказ)

- `cd backend && php artisan test` — OK (у т. ч. `SpaShellTest` для `/chat`).
- `cd backend && npm run build` — OK.
- Чекліст і сценарій: [T06-QA.md](T06-QA.md).

## T06 — Фронт чату (підсумок)

| Елемент | Значення |
|---------|-----------|
| Маршрут | `/chat` → `ChatRoom.vue` |
| Echo | `resources/js/lib/echo.js`, `laravel-echo` + `pusher-js`, env `VITE_REVERB_*` |
| Деградація | Банер + poll 10 с при відсутності ключа або помилці підписки |

## T05 — QA (доказ)

- `cd backend && php artisan test` — OK (`BroadcastChannelTest`, broadcast у `ChatApiTest`).
- `cd backend && npm run build` — OK.
- Доказ / інструкції: [T05-QA.md](T05-QA.md).

## T05 — Real-time (підсумок)

| Елемент | Значення |
|---------|-----------|
| Канали | `private-room.{roomId}`, `private-user.{userId}` у `routes/channels.php` |
| Подія | `App\Events\MessagePosted` → `broadcastAs` **MessagePosted**, `ShouldBroadcast` + черга |
| HTTP | Після створення повідомлення: `broadcast(new MessagePosted($message))->toOthers()` |

## T04 — QA (доказ)

- `cd backend && php artisan test` — OK (`ChatApiTest`).
- `cd backend && npm run build` — OK.
- Браузер (local): [T04-QA.md](T04-QA.md) — сторінка `/__qa/chat-api`, сценарій «Повний цикл».

## T04 — API (підсумок)

Канонічний опис шляхів, схем і помилок: [openapi.yaml](openapi.yaml).

| Метод | Шлях | Примітки |
|-------|------|----------|
| GET | `/api/v1/rooms` | Гості бачать лише `access === 0`. |
| GET | `/api/v1/rooms/{room}/messages` | `before` = `post_id`, `limit` ≤ 100; `meta.next_cursor`. |
| POST | `/api/v1/rooms/{room}/messages` | Idempotency по `(user_id, client_message_id)`; `/me` у `App\Chat\SlashCommandPipeline`. |

## T03 — QA (доказ)

- `cd backend && npm run build` — OK.
- `cd backend && php artisan test` — OK (9 tests, включно з `SpaShellTest`).
- Доказ UI / клавіатура: [T03-QA.md](T03-QA.md) (чекліст Tab/focus-visible; місця під скріншоти).

## T03 — Фронт (підсумок)

- `/` → Blade [`backend/resources/views/spa.blade.php`](../../backend/resources/views/spa.blade.php) + Vue 2.7 + **vue-router** (history): [`AuthWelcome.vue`](../../backend/resources/js/views/AuthWelcome.vue) — таби Вхід / Реєстрація, гість, тема `system|light|dark`, токени `--rp-*` у [`app.css`](../../backend/resources/css/app.css).
- Axios: `withCredentials` + `withXSRFToken`; перед auth POST — `GET /sanctum/csrf-cookie`.
- Laravel: [`web.php`](../../backend/routes/web.php) — `Route::view('/', 'spa')` + `fallback` для deep links SPA.

## T02 — QA (доказ)

- `cd backend && php artisan test` — OK (`AuthApiTest`, throttle → **429**).
- Sanctum `statefulApi()`; логін/вихід через `Auth::guard('web')`.

## T02 — API (підсумок)

| Метод | Шлях | Throttle |
|-------|------|----------|
| POST | `/api/v1/auth/register` | 5 / хв / IP |
| POST | `/api/v1/auth/login` | 5 / хв / IP |
| POST | `/api/v1/auth/guest` | 20 / хв / IP |
| GET | `/api/v1/auth/user` | `auth:sanctum` |
| POST | `/api/v1/auth/logout` | `auth:sanctum` |

## T01 — QA (доказ)

- Міграції + `npm run build`; MySQL Docker — див. попередні записи та `SHOW INDEX` для `post_date` DESC.

## Розташування коду

Додаток Laravel: **`backend/`**.

## Примітка: Vite і Vue 2.7

**Vite ^7** + `@vitejs/plugin-vue2` + **vue-router@3** (Vue 2).
