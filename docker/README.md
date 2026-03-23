# Docker (T83)

## `compose.yaml`

Піднімає **MySQL 8** і **Redis 7** для локальної розробки. Паролі та імена БД — лише для dev; у production використовуйте секрети та окремі політики.

```bash
docker compose -f docker/compose.yaml up -d
```

Переконайтеся, що `backend/.env` вказує на `127.0.0.1` і порти `3306` / `6379`, потім у каталозі `backend/`:

```bash
php artisan migrate
```

## Повний стек у контейнерах

Повноцінний **PHP-FPM + Nginx + Reverb + queue worker** — окремий інкремент T83 (образи, supervisor, healthcheck для WS). Див. [T80-DEPLOY-CHECKLIST.md](../docs/chat-v2/T80-DEPLOY-CHECKLIST.md) і [T83-QA.md](../docs/chat-v2/T83-QA.md).

## Приклад віддаленого деплою

Шаблон кроків без секретів: [deploy.example.sh](./deploy.example.sh).
