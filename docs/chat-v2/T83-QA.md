# T83 — QA (інкремент 1: CI + локальні контейнери + ADR)

## Scope цього інкремента

- **GitHub Actions** `.github/workflows/ci.yml`: `composer test`, `npm ci`, `npm run build`, `npm run test:msg-parse`, `npm run test:country-filter` у каталозі `backend/` на `push`/`pull_request` до `main`.
- **`docker/compose.yaml`**: MySQL 8 + Redis 7 для локальної розробки; інструкції в `docker/README.md`.
- **ADR** [T83-SINGLE-ORIGIN.md](./T83-SINGLE-ORIGIN.md) — один origin vs `api.` та Auth0 audience.
- **Приклад** `docker/deploy.example.sh` — заглушка кроків деплою без секретів.
- **PHPUnit:** у `phpunit.xml` додано `AUTH0_ENABLED=false`, щоб `LandingApiTest` не залежав від локального `.env`.

## Що лишається для повного закриття T83

- Job **деплою по SSH** з secrets, **бекап MySQL перед деплоєм**, перевірка `GET /health/ready` після релізу — за політикою середовища.
- За потреби — повний **Docker**-стек (Nginx, PHP-FPM, Reverb, queue worker) замість лише MySQL/Redis.

## Автоматичні перевірки (локально)

```bash
cd backend && composer test
cd backend && npm ci && npm run build
cd backend && npm run test:msg-parse && npm run test:country-filter
docker compose -f docker/compose.yaml config
```

## Доказ для GitHub Actions

Після першого успішного run на `main`: вставте посилання на run (GitHub → Actions → workflow **CI**) або короткий `run id` у наступному оновленні цього файлу.

## Вердикт

**PASS (інкремент 1)** — після зелених команд вище та успішного CI run.
