# Docker (T83)

## `compose.yaml`

Піднімає **MySQL 8** і **Redis 7** для локальної розробки. Паролі та імена БД — лише для dev; у production використовуйте секрети та окремі політики.

```bash
docker compose -f docker/compose.yaml up -d
```

За замовчуванням **MySQL і Redis не проброшені на хост** (щоб на VPS не конфліктувати з локальним Redis/MySQL). Якщо `php artisan` запускаєте **на хості** з Docker-залежностями:

```bash
cp docker/compose.override.example.yml docker/compose.override.yml
docker compose -f docker/compose.yaml up -d
```

У `backend/.env`: `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1`, потім `php artisan migrate`.

## Профіль `app`: PHP-FPM + Nginx (ліміт завантаження 128MB)

HTTP через контейнери (порт **8080**). У `backend/.env` для цього режиму вкажіть **`DB_HOST=mysql`**, **`REDIS_HOST=redis`** (не `127.0.0.1`).

```bash
docker compose -f docker/compose.yaml --profile app up -d --build
```

- **PHP:** `upload_max_filesize` і `post_max_size` = **128M** (`docker/php/conf.d/uploads.ini`).
- **Nginx:** `client_max_body_size` = **128m** (`docker/nginx/default.conf`).

Після `up` змонтуйте `backend/` (включно з `vendor` після `composer install` на хості або всередині `php`).

### Reverb і queue (`--profile app`)

Піднімаються сервіси **`reverb`** (порт **6001** → `REVERB_SERVER_PORT`) і **`queue`** (`php artisan queue:work`). У `backend/.env` для браузера (Vite) вкажіть зазвичай:

- `VITE_REVERB_HOST=localhost`
- `VITE_REVERB_PORT=6001`
- `VITE_REVERB_SCHEME=http`
- `REVERB_APP_ID` / `REVERB_APP_KEY` / `REVERB_APP_SECRET` — як у [T80](../docs/chat-v2/T80-DEPLOY-CHECKLIST.md); перебудуйте фронт після зміни `VITE_*`.

HTTP лишається на **8080**; WebSocket — окремо на **6001** (типовий локальний сценарій Echo / Pusher-протокол).

Деталі процесів і прод-проксі — [T80-DEPLOY-CHECKLIST.md](../docs/chat-v2/T80-DEPLOY-CHECKLIST.md), перевірка — [T83-QA.md](../docs/chat-v2/T83-QA.md).

## Продакшен: паролі MySQL/Redis (не в git)

1. `cp docker/compose.override.prod.example.yml docker/compose.override.yml`
2. `cp docker/compose.deploy.env.example docker/compose.deploy.env` — заповніть `MYSQL_ROOT_PASSWORD`, `MYSQL_PASSWORD`, `REDIS_PASSWORD` (напр. `openssl rand -base64 32`).
3. У `backend/.env`: ті самі `DB_PASSWORD` і `REDIS_PASSWORD`, що в `compose.deploy.env`; `DB_USERNAME=redpanda`.
4. `deploy.sh` сам додасть `--env-file docker/compose.deploy.env`, якщо файл існує.

Файли `docker/compose.override.yml` і `docker/compose.deploy.env` у `.gitignore`.

**Пароль MySQL змінили в `compose.deploy.env`, а том `mysql_data` уже був?** Змінні `MYSQL_*` при старті контейнера **не** оновлюють пароль існуючого користувача в БД — зробіть `ALTER USER 'redpanda'@'%'` або пересоздайте том. Інакше Laravel (навіть з правильним env у контейнері) отримає **1045 Access denied**.

Після `php artisan optimize` у `bootstrap/cache/config.php` лежить старий пароль — **`deploy.sh` перед `composer install` очищає ці файли**, щоб `package:discover` не ходив у MySQL з кешованим `DB_PASSWORD`.

## Бекап MySQL

Потрібен запущений контейнер `mysql`:

```bash
./docker/backup-mysql.sh
```

Архів: `backups/redpanda-UTC.sql.gz` (каталог задається змінною `BACKUP_DIR`).

## Приклад віддаленого деплою

Кроки деплою на сервері: [deploy.sh](./deploy.sh).
