# T11 — Короткий runbook (Reverb / Redis / здоров’я сервісу)

Повний чекліст **staging / production** (деплой, ENV, проксі WebSocket, черга, backup, real-time після релізу): **[T80-DEPLOY-CHECKLIST.md](T80-DEPLOY-CHECKLIST.md)**.

## Що перевіряти

| Симптом | Ймовірна причина | Дія |
|--------|-------------------|-----|
| У чаті немає live-оновлень, банер «poll» або помилка Echo | **Reverb** не запущений або `VITE_REVERB_*` / `REVERB_*` не збігаються з клієнтом | Запустити `php artisan reverb:start`; перевірити `.env` (host, port, scheme, app key). HTTP і WS працюють окремо: `GET /health/ready` **не** перевіряє Reverb. |
| WS 101 / Echo fail, на nginx лише `location /app` | Laravel Reverb також обслуговує **`/apps`**; без проксі на Reverb запити йдуть у Laravel | Додати `location /apps` → той самий порт, що Reverb (`docker/nginx/host-nginx-reverb-proxy.example.conf`). |
| **426** від Reverb / WS не піднімається, у `location /app/` лише Upgrade | У цьому `location` є **будь-який** `proxy_set_header` → nginx **не** тягне Host з `server`; upstream бачить **Host: 127.0.0.1** | Дублювати в `location /app/` та `/apps`: **`Host $http_host`**, **`X-Forwarded-Proto`**, **`X-Forwarded-For`**, Upgrade, Connection (`host-nginx-reverb-proxy.example.conf`). |
| Браузер тягне `wss://…:6001` або на `reverb` | У бандл потрапив внутрішній `REVERB_*` замість публічного URL | `APP_URL=https://ваш.домен` і порожні `VITE_REVERB_*`, або явно `VITE_REVERB_HOST` / `PORT=443` / `SCHEME=https`; `REVERB_HOST=reverb` лише для PHP у Docker. |
| У логах Reverb: `Starting server on 0.0.0.0:8080`, Docker проброс **6001:6001**, WS не конектиться | Після `php artisan optimize` у **контейнері php** у кеш потрапив дефолтний порт **8080** (`config/reverb.php`), а не **6001** | У `docker/compose.yaml` для **php** (і queue) задано `REVERB_SERVER_PORT: "6001"`; на сервері: `docker compose ... exec php php artisan config:clear && php artisan optimize`, потім `restart reverb`. Або повторний deploy. |
| Reverb: `SQLSTATE[1045]` при зверненні до таблиці **`cache`** | Пароль у **`docker/production.env`** (`DB_PASSWORD`) не збігається з тим, що записано в MySQL для `redpanda` (том `mysql_data` ініціалізували з іншим `MYSQL_PASSWORD`) | З хоста: `docker compose ... exec mysql mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "ALTER USER 'redpanda'@'%' IDENTIFIED BY 'ТОЙ_САМИЙ_ПАРОЛЬ_ЩО_В_DB_PASSWORD'; FLUSH PRIVILEGES;"` (пароль у лапках екранувати за правилами MySQL). Потім `restart reverb queue php`. Перевірка: `docker compose ... exec reverb php artisan db:show`. |
| Під час `docker compose exec` з’являється **`getwd: no such file or directory`** | Поточний каталог на **хості**, з якого викликано `docker compose`, уже не існує (видалили / змінили mount) | Завжди: `cd /var/www/redpanda` (або інший **існуючий** шлях до репо). Або вказати файл явно: `docker compose -f /var/www/redpanda/docker/compose.yaml ...`. Для artisan: `exec -w /var/www/html php ...`. |
| Повільний чат, черга не рухається, jobs зависли | **Redis** потрібен для cache/queue, але недоступний | Перевірити `redis-cli ping`; у `.env` `REDIS_*`. Увімкнути `HEALTH_CHECK_REDIS=true` або перевести cache/queue на redis — тоді `GET /health/ready` покаже `checks.redis`. |
| 503 на `GET /health/ready` | **MySQL** недоступний або мережа до БД | Логи додатку, `DB_*` у `.env`, `mysqladmin ping` / підключення з контейнера. |
| 503 / «maintenance» на всіх шляхах | Режим обслуговування Laravel | `php artisan up`; перевірити `storage/framework/down`. |
| Браузер: **`net::ERR_CONNECTION_RESET`** на `wss://…/app/…`, **`error.log` порожній**, Reverb **без** рядків про з’єднання | Запит **не логують** як помилку або **обривають до upstream**; часто **перевищення ліміту заголовків** (довгі cookie сесії) або RST на TCP | У `server` для сайту: **`large_client_header_buffers 4 32k;`**, перезавантажити nginx. Увімкнути **`access_log`** для цього `server` (окремий файл) і перевірити, чи з’являються рядки **`GET /app/`** при відкритті чату. З **ноутбука** (не з VPS): `curl --http1.1 -vk` з Upgrade на `https://домен/app/…` — чи є **101**. На VPS: **`tcpdump -i lo port 6001`** під час кліку в чаті — чи доходить трафік до Docker. Тимчасово **`php artisan reverb:start --debug`** у контейнері — чи з’являються події. |

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
