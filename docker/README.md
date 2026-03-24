# Docker (T83)

## `compose.yaml`

Один маніфест для **dev і prod**: паролі та імена БД задаються через змінні з дефолтами для локальної розробки (`${VAR:-default}`). У продакшені ті самі ключі передають через **`docker/production.env`** і `--env-file` (робить `deploy.sh` / `backup-mysql.sh`).

**Увага:** значення за замовчуванням у `compose.yaml` (`root` / `redpanda`, Redis без пароля) придатні **лише для dev**. На VPS секрети — у `docker/production.env`. **Канон для доступу додатку до MySQL:** `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`; compose сам підставляє їх у змінні образу `mysql` (`MYSQL_*`). Окремо лише **`MYSQL_ROOT_PASSWORD`** (root для бекапів, це не пароль користувача `redpanda`).

```bash
docker compose -f docker/compose.yaml up -d
```

За замовчуванням **MySQL і Redis не проброшені на хост**. Якщо `php artisan` на **хості** з Docker-залежностями — див. коментарі в [`compose.override.example.yml`](./compose.override.example.yml): потрібні **два** `-f` або `cd docker && docker compose up`.

У `backend/.env`: `DB_HOST=127.0.0.1`, `REDIS_HOST=127.0.0.1`, потім `php artisan migrate`.

## Профіль `app`: PHP-FPM + Nginx (ліміт завантаження 128MB)

HTTP через контейнери (порт **8080**). У повному Docker-режимі в `backend/.env`: **`DB_HOST=mysql`**, **`REDIS_HOST=redis`**.

```bash
docker compose -f docker/compose.yaml --profile app up -d --build
```

- **PHP:** `upload_max_filesize` і `post_max_size` = **128M** (`docker/php/conf.d/uploads.ini`).
- **Nginx:** `client_max_body_size` = **128m** (`docker/nginx/default.conf`).

### Reverb і queue (`--profile app`)

Сервіси **`reverb`** (порт **6001**) і **`queue`**. Для Echo/Vite локально зазвичай `VITE_REVERB_HOST=localhost`, `VITE_REVERB_PORT=6001`, `VITE_REVERB_SCHEME=http`; у проді — `wss` і публічний хост (див. [T80](../docs/chat-v2/T80-DEPLOY-CHECKLIST.md)).

## Продакшен: `docker/production.env` (не в git), **без** окремого prod-override

1. `cp docker/production.env.example docker/production.env` — заповніть секрети (`openssl rand -base64 32` тощо).
2. Рекомендовано **`ln -sf ../docker/production.env backend/.env`** (з `backend/`), щоб `npm run build` у `deploy.sh` бачив `VITE_*`. **`APP_URL` має бути `https://…`** публічного домену (інакше mixed content: асети з `@vite` / `url()` підуть через `http://`).
3. **`docker/compose.override.yml` для прод не потрібен** — паролі MySQL/Redis і healthcheck’и вже в `compose.yaml`. Якщо на сервері лишився старий override лише заради prod-секретів — після оновлення репозиторію його можна **видалити**, щоб не дублювати й не роз’їжджатися з каноном.
4. `deploy.sh` додає `--env-file docker/production.env`, якщо файл існує; інакше — legacy `docker/compose.deploy.env`.

Файли `docker/production.env`, `docker/compose.deploy.env`, `docker/compose.override.yml` у `.gitignore`.

**Пароль MySQL змінили лише в `production.env`, а том `mysql_data` уже ініціалізований?** Змінні при старті контейнера **не** оновлюють пароль у БД — спочатку **`./docker/rotate-mysql-passwords.sh`** (з бекапом), потім оновіть `production.env` і `compose up`. Інакше буде **1045**.

`deploy.sh` перед `composer install` очищає `bootstrap/cache/*`, щоб не тягнути старий кешований `DB_PASSWORD`.

**Не дублюйте один ключ кілька разів** у `docker/production.env` (наприклад, два `DB_PASSWORD=`): у Compose/`--env-file` зазвичай **перемагає останній** рядок — легко отримати 1045 у PHP, бо пароль у файлі й у MySQL роз’їдуться.

## Безпека продакшену

| Область | Що зробити |
|--------|------------|
| **MySQL** | У файлі одна логіка пароля додатку: **`DB_PASSWORD`** (разом з `DB_USERNAME` / `DB_DATABASE`). Окремо **`MYSQL_ROOT_PASSWORD`** для root. Не дублюйте другий рядок `DB_PASSWORD`. |
| **Redis** | Непорожній `REDIS_PASSWORD` — контейнер увімкне `requirepass`; у Laravel той самий пароль у `REDIS_PASSWORD`. |
| **Laravel** | `APP_DEBUG=false`, `APP_ENV=production`, стабільний `APP_KEY`. Розгляньте `SESSION_ENCRYPT=true` (у шаблоні `production.env.example` вже увімкнено для проду). |
| **Проксі** | `TRUSTED_PROXIES` — IP хостового nginx (часто `127.0.0.1`; якщо трафік іде з docker bridge — додайте відповідну адресу). Уникайте `*` без явної причини. |
| **Логи** | У шаблоні прод: `LOG_LEVEL=warning` (зменшує витік деталей у логах). |
| **Ротація MySQL** | Після бекапу: [`rotate-mysql-passwords.sh`](./rotate-mysql-passwords.sh) — без змінних бере поточний root з контейнера `mysql` і генерує нові паролі (вивід скопіювати в `production.env`); або задайте `OLD_*` / `NEW_*` вручну. |

## Бекап MySQL

Запущений контейнер `mysql` і той самий `--env-file`, що в деплої:

```bash
./docker/backup-mysql.sh
```

Архіви: `$REPO_DIR/backups/redpanda-<UTC>.sql.gz`.

### Перед міграціями у `deploy.sh`

**`BACKUP_BEFORE_DEPLOY=1`** викликає лише `docker/backup-mysql.sh`. Потрібен уже запущений `mysql` (перший деплой — тимчасово вимкніть або спочатку `up` без бекапу).

### Cron і retention

Приклад (шляхи підставити свої):

```cron
0 3 * * * REPO_DIR=/var/www/redpanda BACKUP_DIR=/var/www/redpanda/backups /var/www/redpanda/docker/backup-mysql.sh >>/var/www/redpanda/backups/backup.log 2>&1
15 3 * * * find /var/www/redpanda/backups -maxdepth 1 -name 'redpanda-*.sql.gz' -mtime +14 -delete
```

## Віддалений деплой

[deploy.sh](./deploy.sh). Перевірка з SSH — [T80, «Перевірка на VPS (SSH)»](../docs/chat-v2/T80-DEPLOY-CHECKLIST.md).
