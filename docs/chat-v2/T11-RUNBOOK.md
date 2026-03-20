# T11 — Короткий runbook (Reverb / Redis / здоров’я сервісу)

## Що перевіряти

| Симптом | Ймовірна причина | Дія |
|--------|-------------------|-----|
| У чаті немає live-оновлень, банер «poll» або помилка Echo | **Reverb** не запущений або `VITE_REVERB_*` / `REVERB_*` не збігаються з клієнтом | Запустити `php artisan reverb:start`; перевірити `.env` (host, port, scheme, app key). HTTP і WS працюють окремо: `GET /health/ready` **не** перевіряє Reverb. |
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
