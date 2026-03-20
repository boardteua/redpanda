# redpanda

Чат v2 (greenfield): Laravel + Vue 2.7 + MySQL; вимоги в `docs/board-te-ua/`; оркестрація — [docs/chat-v2/AGENT-ORCHESTRATION.md](docs/chat-v2/AGENT-ORCHESTRATION.md). Стан спринту: [docs/chat-v2/STATUS.md](docs/chat-v2/STATUS.md).

**Код додатку:** каталог [`backend/`](backend/) (`composer install`, `cp .env.example .env`, `php artisan key:generate`, `npm ci`, `npm run build`).

**Порожня сторінка в браузері:** якщо колись запускали `npm run dev`, у `backend/public/` з’являється файл **`hot`** — тоді HTML тягне скрипти з **http://127.0.0.1:5173**. Без запущеного Vite (`npm run dev`) інтерфейс не змонтується. Варіанти: (1) у другому терміналі `cd backend && npm run dev`, або (2) видалити `backend/public/hot` і мати актуальний `npm run build` — тоді достатньо лише `php artisan serve`.

Локальний **MySQL** (наприклад OrbStack): `docker run -d --name rp-mysql-redpanda -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=redpanda -p 3307:3306 mysql:8 --character-set-server=utf8mb4 --collation-server=utf8mb4_unicode_ci` — дочекатися `mysqladmin ping`, у `.env` вказати `DB_PORT=3307` та пароль.

**Auth API (T02):** префікс `POST|GET /api/v1/auth/...`, Sanctum SPA (cookie + CSRF).

**Vue (T03):** головна `/` — форми вхід / реєстрація / гість; QA-чекліст — [docs/chat-v2/T03-QA.md](docs/chat-v2/T03-QA.md).

**Chat REST (T04):** `GET /api/v1/rooms`, `GET|POST /api/v1/rooms/{id}/messages`; контракт API — [docs/chat-v2/openapi.yaml](docs/chat-v2/openapi.yaml); браузерний QA (лише `APP_ENV=local`) — [`/__qa/chat-api`](http://127.0.0.1:8000/__qa/chat-api); деталі — [docs/chat-v2/T04-QA.md](docs/chat-v2/T04-QA.md).

**Broadcast (T05):** канали `private-room.{id}` / `private-user.{id}`, подія **MessagePosted**; локально — `php artisan reverb:start` (див. `.env` `REVERB_*`); QA — [docs/chat-v2/T05-QA.md](docs/chat-v2/T05-QA.md).

**Vue чат (T06):** після входу — **`/chat`** (стрічка, Echo, poll fallback); потрібні `VITE_REVERB_*` у `.env` для WS; QA — [docs/chat-v2/T06-QA.md](docs/chat-v2/T06-QA.md).

**Панель чату (T07):** сайдбар **320px** на `/chat` (вкладки як у board.te, off-canvas на вузькому екрані); QA — [docs/chat-v2/T07-QA.md](docs/chat-v2/T07-QA.md).

**Приват і соціальне (T08):** REST приват, друзі, ігнор; панель привату + `/msg нік` у композері кімнати; WS **PrivateMessagePosted** на `private-user.{id}`; QA — [docs/chat-v2/T08-QA.md](docs/chat-v2/T08-QA.md).

**Спостережуваність (T11):** `GET /up`, `GET /health/ready`; JSON-логи — канал `structured` у `config/logging.php`; QA — [docs/chat-v2/T11-QA.md](docs/chat-v2/T11-QA.md), runbook — [docs/chat-v2/T11-RUNBOOK.md](docs/chat-v2/T11-RUNBOOK.md).

**Модерація MVP (T12):** бан IP, слова-фільтр, mute/kick на користувачах; API `/api/v1/mod/*` для `user_rank ≥ 1` — [docs/chat-v2/T12-QA.md](docs/chat-v2/T12-QA.md).

**Інтеграція (T14):** скрізний чекліст і API-smoke — [docs/chat-v2/T14-QA.md](docs/chat-v2/T14-QA.md) (`php artisan test --filter=IntegrationFlowApiTest`).

Пам’ять для агентів: [AGENTS.md](AGENTS.md) (оновлення — [docs/chat-v2/CONTINUAL-LEARNING.md](docs/chat-v2/CONTINUAL-LEARNING.md)).
