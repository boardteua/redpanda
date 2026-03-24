# T11 — Короткий runbook (Reverb / Redis / здоров’я сервісу)

Повний чекліст **staging / production** (деплой, ENV, проксі WebSocket, черга, backup, real-time після релізу): **[T80-DEPLOY-CHECKLIST.md](T80-DEPLOY-CHECKLIST.md)**.

## Що перевіряти

| Симптом | Ймовірна причина | Дія |
|--------|-------------------|-----|
| У чаті немає live-оновлень, банер «poll» або помилка Echo | **Reverb** не запущений або `VITE_REVERB_*` / `REVERB_*` не збігаються з клієнтом | Запустити `php artisan reverb:start`; перевірити `.env` (host, port, scheme, app key). HTTP і WS працюють окремо: `GET /health/ready` **не** перевіряє Reverb. |
| У логах Reverb: `Starting server on 0.0.0.0:8080`, Docker проброс **6001:6001**, WS не конектиться | Після `php artisan optimize` у **контейнері php** у кеш потрапив дефолтний порт **8080** (`config/reverb.php`), а не **6001** | У `docker/compose.yaml` для **php** (і queue) задано `REVERB_SERVER_PORT: "6001"`; на сервері: `docker compose ... exec php php artisan config:clear && php artisan optimize`, потім `restart reverb`. Або повторний deploy. |
| Reverb: `SQLSTATE[1045]` при зверненні до таблиці **`cache`** | `DB_*` у контейнера не збігаються з паролем користувача в MySQL (volume створено з іншим `MYSQL_PASSWORD`) або тимчасовий збій до готовності БД | Вирівняти `DB_PASSWORD` / `MYSQL_PASSWORD` у `production.env` з фактичним користувачем у MySQL; перезапустити mysql/app після зміни пароля. |
| Повільний чат, черга не рухається, jobs зависли | **Redis** потрібен для cache/queue, але недоступний | Перевірити `redis-cli ping`; у `.env` `REDIS_*`. Увімкнути `HEALTH_CHECK_REDIS=true` або перевести cache/queue на redis — тоді `GET /health/ready` покаже `checks.redis`. |
| 503 на `GET /health/ready` | **MySQL** недоступний або мережа до БД | Логи додатку, `DB_*` у `.env`, `mysqladmin ping` / підключення з контейнера. |
| 503 / «maintenance» на всіх шляхах | Режим обслуговування Laravel | `php artisan up`; перевірити `storage/framework/down`. |

## Процеси

- **PHP (octane/serve):** обслуговує HTTP, включно з `/api/*`, `/health/ready`, `/up`.
- **Reverb:** окремий WebSocket-сервер; має бути запущений у production поряд з веб-процесом (systemd, supervisor, Docker sidecar тощо).

## Корисні команди

```bash
curl -sS http://127.0.0.1:8000/health/ready | jq .
curl -sS -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/up
tail -f backend/storage/logs/structured.log   # якщо увімкнено канал structured
```

## Примітка

Поточний типовий `.env` у проєкті: `CACHE_STORE=database`, `QUEUE_CONNECTION=database` — тоді Redis для HTTP-readiness **не** перевіряється, доки явно не ввімкнено `HEALTH_CHECK_REDIS` або не змінено драйвери на redis.
