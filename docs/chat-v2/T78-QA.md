# T78 — QA evidence (AI-агенти / LLM, `/llms.txt`)

## Дата

2026-03-23

## Перевірки

- **`php artisan test --filter=LlmsTxtTest`** — PASS (див. вивід CI / локально).
- **`curl -sI`** (локально, `APP_URL=http://127.0.0.1:8000`):

```bash
curl -sI "http://127.0.0.1:8000/llms.txt" | tr -d '\r'
```

Очікування: `HTTP/1.x 200`, заголовок `Content-Type` містить `text/markdown` та `charset=utf-8`.

- **Тіло `/llms.txt`:** немає літералу `__APP_URL__`; є посилання на `/api/v1` та `/docs/...`.
- **Секрети:** у відповідях `/llms.txt`, `/docs/openapi.yaml`, markdown-доках немає `.env` значень (ручний скринінг).
- **`npm run build`:** без змін у JS-логіці, крім `spa.blade.php` — за потреби оператора після змін Blade.

## Примітки

- Маршрути `/docs/*` читають файли з **батьківського каталогу** відносно `backend/` (монорепо). Якщо у продакшені деплой лише каталогу `backend/` без `docs/` та `project-specs/`, ці URL повернуть **404** — у `llms.txt` є пояснення для клону Git.
