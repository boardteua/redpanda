# T80 — Staging / production: деплой, змінні середовища, спостережуваність, real-time

Канонічний шлях виводу описано **без прив’язки** до конкретного хостингу (Forge, Docker, systemd): адаптуйте під політику команди. Деталі типових збоїв — [T11-RUNBOOK.md](T11-RUNBOOK.md). Команди кешування узгоджені з [Laravel 13.x Deployment](https://laravel.com/docs/13.x/deployment).

## Топологія процесів

| Процес | Призначення |
|--------|-------------|
| HTTP (PHP-FPM, Octane, `php artisan serve` — лише dev) | API, Blade `spa`, `GET /health/ready`, `GET /up` |
| **Laravel Reverb** | WebSocket для Echo / real-time чату |
| **Queue worker** (`queue:work` або еквівалент) | `ShouldBroadcast` і інші jobs; без воркера push у чат не доходить |
| MySQL | Основні дані |
| Redis | Опційно: cache/queue; **обов’язково** для горизонтального масштабу Reverb (`REVERB_SCALING_ENABLED=true`) |

## Перед деплоєм

- [ ] Резервна копія БД за політикою команди (розклад, retention; **секрети підключення не в репо**).
- [ ] Оновлений `.env` / secret store для середовища; перевірка [таблиці змінних](#таблиця-змінних-staging--production) нижче.
- [ ] Для фронту: збірка Vite у каталозі `backend/` з **продакшн-значеннями** `VITE_*` (див. [Vite env](https://vite.dev/guide/env-and-mode.html)); інакше клієнт піде не на той Reverb-хост.
- [ ] За балансувальником: `TRUSTED_PROXIES` і коректні `X-Forwarded-*` (див. коментар у `backend/.env.example`).
- [ ] **Безпека Docker-прод:** сильні **`DB_PASSWORD`** / **`DB_USERNAME`** / **`DB_DATABASE`**, окремо **`MYSQL_ROOT_PASSWORD`**, **`REDIS_PASSWORD`** у `docker/production.env` (не дефолти `root`/`redpanda`); один рядок `DB_PASSWORD` без дублікатів; ротація — `docker/rotate-mysql-passwords.sh` після бекапу (див. `docker/README.md`).
- [ ] **`APP_DEBUG=false`**, за потреби **`SESSION_ENCRYPT=true`**, помірний **`LOG_LEVEL`** (напр. `warning`) на проді.

## Кроки деплою (порядок типовий)

1. Увімкнути обслуговування (за бажанням): `php artisan down` (або ваш zero-downtime сценарій).
2. Оновити код: `git pull` / артефакт CI.
3. Залежності PHP: `composer install --no-dev --optimize-autoloader` (у `backend/`).
4. Залежності фронту та збірка: `npm ci` → `npm run build` (у `backend/`).
5. Міграції: `php artisan migrate --force`.
6. Кеші додатку (Laravel 13): `php artisan optimize` (або окремо `config:cache`, `route:cache`, `view:cache` за потреби).
7. Перезапуск довгоживучих процесів: **queue workers**, **Reverb**, PHP-FPM/Octane — щоб підхопити новий код і кеші.
8. Вимкнути обслуговування: `php artisan up`.

**Примітка:** після `config:cache` у файлах `config/*` має використовуватися лише `env()` там, де це дозволено документацією Laravel; інакше закешовані значення не оновляться з `.env`.

## Reverse proxy і WebSocket

- HTTP і **WS** — різні потреби: проксі має прокидати `Upgrade` та `Connection` для Reverb (приклад у [документації Reverb](https://laravel.com/docs/13.x/reverb)).
- Reverb очікує **два** префікси URI: **`/app/`** (WebSocket; краще з кінцевим `/`, щоб не перехопити **`/apps`**) і **`/apps`** (HTTP API). Якщо проксується лише один з них — real-time ламається. У **кожному** з цих `location` задайте повний набір **`proxy_set_header`** (Host, `X-Forwarded-Proto`, `X-Forwarded-For`, Upgrade, Connection): інакше nginx **не** успадковує заголовки з `server`, і до Reverb піде **`Host: 127.0.0.1`** → типова відповідь **426** / обрив WS (див. `docker/nginx/host-nginx-reverb-proxy.example.conf`).
- У `docker/production.env`: **`REVERB_HOST=reverb`** (або інше ім’я сервісу в мережі Docker) для **PHP → Reverb**; **`VITE_REVERB_*`** або порожні значення з **`APP_URL=https://…`** (після збірки Vite підставляє публічний хост і **443**) — для **браузера**. Не використовуйте `REVERB_HOST=reverb` у `VITE_REVERB_HOST`.
- Для великої кількості з’єднань — підняти ліміти worker/process (nginx `worker_connections`, supervisor `minfds` тощо) за рекомендаціями Laravel.

**Діагностика, коли в браузері `ERR_CONNECTION_RESET`, а `error.log` порожній:** nginx може **закрити з’єднання без рядка в error.log** (наприклад, **Request URI Too Large** / ліміт заголовків). WebSocket-рукостискання несе **Cookie** — при великій сесії додайте в `server` **`large_client_header_buffers 4 32k;`**. Увімкніть **access_log** для vhost і перевірте наявність **`GET /app/`** під час відкриття чату. Порівняйте: **curl Upgrade з іншої машини** vs **браузер**; на сервері **`tcpdump -i lo port 6001`** — чи є пакети до Reverb, коли рветься клієнт.

## Масштабування Reverb

- Кілька інстансів Reverb за балансувальником: `REVERB_SCALING_ENABLED=true` і доступний Redis (див. [Reverb scaling](https://laravel.com/docs/13.x/reverb)).

## Черга

- У `.env.example` за замовчуванням `QUEUE_CONNECTION=database` — потрібен **постійно запущений** `php artisan queue:work` (або кілька воркерів).
- Якщо `QUEUE_CONNECTION=redis` — переконатися, що Redis доступний; тоді `GET /health/ready` перевіряє Redis автоматично (або встановіть `HEALTH_CHECK_REDIS=true` для явної перевірки).

## Спостережуваність і балансувальник

| Ендпоінт | Призначення |
|----------|-------------|
| `GET /up` | Перевірка «додаток не в down» (Laravel) |
| `GET /health/ready` | JSON: БД; Redis — якщо увімкнено політикою (див. `HealthController`, `HEALTH_CHECK_REDIS`) |

**Важливо:** `health/ready` **не** перевіряє, що процес Reverb запущений. Для алертів узгодьте окремий health для WS або синтетичний клієнт.

- Структуровані логи: див. коментарі в `backend/.env.example` (`LOG_STACK`, `structured.log`).
- Алерти (PagerDuty, cloud monitoring тощо) — за домовленістю команди; прив’язати до `5xx`, латентності та зовнішніх перевірок WS за потреби.

## Fail-fast (операційні очікування)

| Симптом | Ймовірна причина |
|---------|------------------|
| 503 на `health/ready`, `checks.database: fail` | MySQL недоступний або невірні `DB_*` |
| 503, `checks.redis: fail` | Redis потрібен (кеш/черга або `HEALTH_CHECK_REDIS=true`), але недоступний |
| Чат без live, polling / помилки Echo | Reverb не запущений або розсинхрон `REVERB_*` / `VITE_REVERB_*` |
| Повідомлення не розходяться між клієнтами | Не запущений queue worker |
| **503** на `POST /api/v1/images` (multipart) з повідомленням про storage | Каталог **`storage/app/chat-images`** відсутній або **не записуваний** процесом PHP (`chown`/`chmod` на хості або в томі Docker); перевірте також **`client_max_body_size`** (nginx) і **`upload_max_filesize`** / **`post_max_size`** (PHP) — інакше тіло не доходить до Laravel (**T98**) |

## Регресія real-time після деплою

Короткий сценарій (два клієнти / два браузери):

1. Увійти в той самий чат (або кімнату) двома сесіями.
2. Надіслати повідомлення з **А** — з’являється у **Б** без повного перезавантаження (Echo / WS).
3. За наявності presence — перевірити оновлення індикаторів узгоджено з прийняттям **T20**.

Детальніше: [AGENT-ORCHESTRATION.md](AGENT-ORCHESTRATION.md) → «Локальне середовище real-time при завершенні задачі».

Приймальні критерії real-time на prod (банер poll, WS **101**, `location /apps`) — **[T96-QA.md](T96-QA.md)**.

---

## Таблиця змінних (staging / production)

Повний приклад і коментарі — **`backend/.env.example`**. Нижче — **обов’язкові або критичні** для робочого чату в мережі.

| Змінна | Чому важлива |
|--------|----------------|
| `APP_KEY` | Шифрування, сесії; має бути стабільним у середовищі |
| `APP_ENV`, `APP_DEBUG` | У prod: `production`, `false` |
| `APP_URL` | URL додатку (посилання, Sanctum, генерація абсолютних URL) |
| `SANCTUM_STATEFUL_DOMAINS` | Домени SPA для cookie-сесії на API |
| `DB_*` | Підключення до MySQL |
| `SESSION_*` | За потреби `SESSION_DOMAIN` для піддоменів |
| `BROADCAST_CONNECTION=reverb` | Як у репо для Echo |
| `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` | Додаток Reverb |
| `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME` | Мають відповідати тому, що бачить **сервер** і проксі |
| `REVERB_SCALING_ENABLED` | `true` при кількох інстансах Reverb + Redis |
| `VITE_REVERB_*` | Мають відповідати публічному WS з точки зору **браузера** |
| `QUEUE_CONNECTION` | Визначає, куди йдуть broadcast-jobs |
| `REDIS_*` | Якщо cache/queue на redis або scaling Reverb |
| `HEALTH_CHECK_REDIS` | Примусова перевірка Redis у readiness |
| `TRUSTED_PROXIES` | За reverse proxy — для коректного IP і схеми |
| `LOG_*` | Рівень логів і канали для prod |
| `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_*` | Транзакційна пошта (скидання пароля, welcome тощо); Gmail / SMTP — див. [MAIL-SMTP.md](MAIL-SMTP.md) (**T108**) |

**Без секретів у Git:** реальні значення лише в secret manager / `.env` на сервері.

### Пошта (SMTP)

- Повний гайд (Gmail App Password, порти 587/465, troubleshooting): **[MAIL-SMTP.md](MAIL-SMTP.md)**.
- Локально: **`MAIL_MAILER=log`** або Mailpit за **`backend/docker-compose.mailpit.yml`**.

---

## Перевірка на VPS (SSH)

Чекліст після змін nginx, Compose або змінних Reverb/Vite (узгоджено з `docker/README.md` і `docker/deploy.sh`).

- [ ] З хоста: **`127.0.0.1:8080`** відповідає контейнерному nginx (`8080:80` у `docker/compose.yaml`). Напр.: `curl -sI http://127.0.0.1:8080 | head -n1`.
- [ ] З хоста: **`127.0.0.1:6001`** слухає Reverb (`6001:6001`). Напр.: `ss -lntp | grep 6001` або перевірка після `docker compose ... ps`.
- [ ] У системному nginx для публічного домену: **`location /app/`** (або ваш шлях WS) узгоджений із **`VITE_REVERB_*`** / **`REVERB_*`** (схема `wss`, хост, шлях); після змін env — повна перезбірка фронту в деплої.
- [ ] **Vite на проді:** скрипт **`docker/deploy.sh`** перед **`npm run build`** копіює **`docker/production.env`** (або **`compose.deploy.env`**) у **`backend/.env.production`** і після збірки видаляє файл — щоб **`REVERB_APP_KEY`** потрапляв у бандл навіть без робочого symlink **`backend/.env`**. Якщо після деплою знову з’являється банер poll — перевірте, що на сервері актуальний **`deploy.sh`** (`git pull`) і що крок збірки не обходиться вручну без цього env.
- [ ] У репозиторії на сервері: **`docker/production.env`** (або legacy **`compose.deploy.env`**) на місці; **`docker/compose.yaml`** канонічний (prod-секрети через `--env-file`, без обов’язкового prod-override). Якщо є **`docker/compose.override.yml`** — лише для локальних нюансів (порти), не дублювати прод-паролі окремо від `production.env`.
- [ ] Змінні деплою/бекапу на хості (GitHub SSH, systemd, `profile.d`): **`REPO_DIR`**, **`DEPLOY_GIT_REF`**, опційно **`DEPLOY_HEALTH_URL`**, **`BACKUP_BEFORE_DEPLOY`**, **`BACKUP_DIR`** — за домовленістю; див. коментарі в `docker/deploy.sh`.
- [ ] Публічний smoke: **`https://board.te.ua/health/ready`** (або ваш `DEPLOY_HEALTH_URL`) повертає успішну відповідь після деплою.

## Залежності та security advisories (T107)

- На кожен PR/push у **main** у GitHub Actions виконується job **Dependency audit (informative, T107)**: `composer audit --format=json` і `npm audit --package-lock-only --json` у каталозі **`backend/`**; підсумок у **Summary** кроку workflow, повні JSON — артефакт **`dependency-audits`**. Крок **не** ламає CI за наявності low-severity; ручний перегляд і bump версій — окремо від аудиту.
