# T11 — Спостережуваність (QA)

**Задача:** структуровані логи, liveness/readiness, короткий runbook для Reverb/Redis.

## Автоматичні перевірки

```bash
cd backend && php artisan test --filter=HealthEndpointTest
cd backend && php artisan test
cd backend && npm run build
```

## Health

| Endpoint | Призначення |
|----------|-------------|
| `GET /up` | Liveness (Laravel): процес відповідає; HTML-відповідь. |
| `GET /health/ready` | Readiness: JSON `status` + `checks` (`database`, опційно `redis`). **503** якщо перевірки не пройдені. |

```bash
curl -sS http://127.0.0.1:8000/up -o /dev/null -w "%{http_code}\n"
curl -sS http://127.0.0.1:8000/health/ready | jq .
```

Redis у readiness перевіряється, якщо `HEALTH_CHECK_REDIS=true` або `CACHE_STORE` / `QUEUE_CONNECTION` вказують на redis.

## Структуровані логи

- Канал **`structured`** у `config/logging.php` — **JSON**, один об’єкт на рядок у `storage/logs/structured.log`.
- Глобальний middleware додає в контекст логів: `request_id`, `http_method`, `path`; після автентифікації — `user_id` (подія `Authenticated`).
- Для демо одного рядка на запит: у `.env` тимчасово `LOG_HTTP_SUMMARY=true` і `LOG_STACK=single,structured` (або додати `structured` до stack).

Приклад згенерованого рядка (формат Monolog JSON, поля можуть відрізнятися за версією):

```json
{"message":"http.request.summary","context":{"route":null,"request_id":"…","http_method":"GET","path":"/health/ready"},"level":200,"level_name":"INFO","channel":"local","datetime":"…"}
```

Runbook: [T11-RUNBOOK.md](T11-RUNBOOK.md).

## Вердикт

| Поле | Значення |
|------|----------|
| **Статус** | PASS (за наявності доказів з команд вище) |
| **Дата** | 2026-03-20 |
