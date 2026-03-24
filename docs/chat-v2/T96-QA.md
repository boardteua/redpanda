# T96 — QA: real-time (Echo / Reverb) на production, банер poll

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) → **T96**.  
**Мета:** після типового деплою на публічному домені **немає** банера «Реалтайм недоступний — оновлення через опитування»; WebSocket до Reverb стабільний (**101**), без стійкого `pusher:error` **4001** / `Application does not exist`.

## Критерії PASS

1. **UI:** у шапці кімнати чату **немає** `role="status"` з текстом про реалтайм і опитування (`ChatRoomHeader.vue`).
2. **DevTools → Network → WS:** з’єднання на шлях на кшталт **`/app/{REVERB_APP_KEY}`** — статус **101 Switching Protocols**; у кадрах немає повторюваних помилок **4001**.
3. **Два клієнти:** повідомлення з сесії **А** з’являється в **Б** без повного reload (узгоджено з **T06** / **T20**).
4. **Процеси на сервері:** контейнери (або процеси) **reverb** і **queue** у стані **Up** (`docker compose ps` у каталозі деплою, напр. `/var/www/redpanda`).
5. **Хостовий nginx:** є **обидва** `location` — **`/app/`** (з кінцевим `/`, щоб не перехопити `/apps`) і **`/apps`**, кожен з **повним** набором `proxy_set_header` (див. [docker/nginx/host-nginx-reverb-proxy.example.conf](../../docker/nginx/host-nginx-reverb-proxy.example.conf) і [Laravel Reverb — Web server](https://laravel.com/docs/reverb#web-server)).

## Швидкі перевірки ззовні (без секретів у репо)

Замініть `HOST` на публічний домен (наприклад `new.board.te.ua`).

```bash
HOST=new.board.te.ua

# Очікування: не «голий» 404 від nginx на весь префікс /apps (якщо 404 — часто немає location /apps).
curl -sI "https://$HOST/apps" | head -n5

# Перевірка, що /app/ взагалі потрапляє на upstream (ключ підставте з REVERB_APP_KEY на сервері, не комітити).
# curl -sI "https://$HOST/app/YOUR_REVERB_APP_KEY" | head -n5
```

**Інтерпретація:** відповідь **404** на `GET https://$HOST/apps` з типовою сторінкою nginx часто означає, що **`location /apps` не налаштований** — Echo/Reverb HTTP API недоступний з браузера; WebSocket також зазвичай ламається або деградує. Порівняйте з канонічним фрагментом у репозиторії та зробіть `nginx -t` + `reload`.

## Перевірка через MCP Chrome DevTools (Cursor)

Сервер MCP: **`user-chrome-devtools`**.

1. Відкрити `https://HOST/chat` (або кімнату з `?room=`).
2. **Список сторінок** → обрати вкладку з чатом.
3. **Snapshot (a11y):** переконатися, що **немає** рядка «Реалтайм недоступний…».
4. **Network:** відфільтрувати **WebSocket** — має бути з’єднання **101** на `/app/…`.
5. Зафіксувати у PR / коментарі до задачі: URL, час перевірки, PASS/FAIL (без cookies / токенів).

## Перевірка з контейнера (опційно)

У каталозі з `docker/compose.yaml` (на сервері):

```bash
docker compose --env-file docker/production.env -f docker/compose.yaml --profile app ps
```

Очікування: сервіси **reverb** і **queue** (або ваш аналог воркера) — **Up**. Для діагностики БД з точки зору Reverb див. runbook **T11** / **T80**.

## Журнал перевірок (заповнює оператор / агент)

| Дата (UTC) | Хост | Результат | Примітки |
|------------|------|-----------|----------|
| 2026-03-24 | new.board.te.ua | **FAIL** | MCP snapshot: банер poll **присутній**; `curl -sI https://new.board.te.ua/apps` → **404**; у списку мережевих запитів сторінки не зафіксовано успішного WS (потрібні правки хостового nginx / upstream). |

Після досягнення PASS — оновити рядок таблиці та позначити **T96** виконаною в чеклісті з посиланням на цей файл.
