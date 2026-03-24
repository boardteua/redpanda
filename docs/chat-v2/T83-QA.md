# T83 — QA (CI/CD, Docker, деплой, rollback)

## Deliverables у репозиторії

| Артефакт | Призначення |
|----------|-------------|
| `.github/workflows/ci.yml` | PHP-тести, Vite build, Node unit tests, валідація `docker compose config`; опційний **deploy** після зеленого CI |
| `docker/compose.yaml` | MySQL 8, Redis 7; профіль **`app`**: PHP-FPM, Nginx (:8080), **Reverb** (:6001), **queue worker** |
| `docker/README.md` | Запуск, змінні для Reverb/Vite |
| `docker/deploy.example.sh` | Шаблон: бекап MySQL → оновлення коду → migrate / optimize / перезапуск |
| [T83-SINGLE-ORIGIN.md](./T83-SINGLE-ORIGIN.md) | ADR: один origin vs `api.`, Auth0 audience vs callback URLs |
| [T83-ROLLBACK.md](./T83-ROLLBACK.md) | Відкат коду, БД, фронту |

## Локальні перевірки

```bash
docker compose -f docker/compose.yaml config
cd backend && composer test
cd backend && npm ci && npm run build
cd backend && npm run test:msg-parse && npm run test:country-filter
```

Профіль додатку (після `composer install` у `backend/`):

```bash
docker compose -f docker/compose.yaml --profile app up -d --build
```

У `backend/.env` для Echo з хоста: `DB_HOST=mysql`, `REDIS_HOST=redis`, `VITE_REVERB_HOST=localhost`, `VITE_REVERB_PORT=6001`, `VITE_REVERB_SCHEME=http`, плюс заповнені `REVERB_APP_*`.

## GitHub Actions

- **CI:** кожен PR / push у `main` — job **`test`** (Compose validate, Composer, Vite build, PHPUnit, Node unit tests). Деплой не стартує без успішного **`test`** (`needs: test`).
- **Deploy:** job **`deploy`** виконується лише на **push** у `main`, якщо задана змінна **`DEPLOY_HOST`**. Потрібні також:
  - **Variables:** `DEPLOY_USER`, `DEPLOY_REPO_DIR` (корінь репо на сервері, де лежить `docker/deploy.sh`)
  - **Secrets:** `DEPLOY_SSH_KEY` (приватний ключ SSH)
  - **Environment `production`:** за бажанням — required reviewers / protection (branch protection на `main` — політика команди).

На сервері налаштуйте `docker/production.env` і змінні для `docker/deploy.sh` (див. `docker/README.md`, T80). Окремий prod-`compose.override.yml` не потрібен.

## Доказ (PASS)

1. Успішний run workflow **CI** на `main` (посилання або run id): _________________
2. Після налаштування deploy — timestamp успішного деплою та `curl` **GET /health/ready**: _________________
3. (Опційно) Підтвердження бекапу перед деплоєм (файл, розмір): _________________
4. Короткий чек real-time (два клієнти) — за [T80](./T80-DEPLOY-CHECKLIST.md): _________________

**Вердикт:** PASS після зелених локальних команд і успішного CI; рядки 2–4 — після першого реального staging/prod-деплою (заповнює оператор).
