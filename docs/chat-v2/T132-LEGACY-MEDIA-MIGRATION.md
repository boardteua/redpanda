# T132 — Медіа legacy: avatar, uploads, ремап URL

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) — **T132**.  
**Runbook:** [T128-LEGACY-PROD-IMPORT-RUNBOOK.md](T128-LEGACY-PROD-IMPORT-RUNBOOK.md).  
**Аватарки (T113):** [T113-LEGACY-AVATARS.md](T113-LEGACY-AVATARS.md).

## Джерело на board.te.ua (типові шляхи)

- **Аватарки:** `/var/www/board.te.ua/html/avatar/` (або еквівалент на хості)
- **Uploads:** `/var/www/board.te.ua/html/uploads/`

На **одному** сервері: у `.env` — абсолютні шляхи (`/var/www/board.te.ua/...` → `/var/www/redpanda/...`); Artisan використовує **локальний rsync** (без `-e ssh`). Якщо джерело в форматі **`user@host:/path`** — тоді **SSH**.

## 1. Копіювання аватарок (команда T113)

```bash
cd backend && php artisan chat:legacy-sync-avatars --dry-run
php artisan chat:legacy-sync-avatars
```

Змінні: `LEGACY_AVATAR_RSYNC_SOURCE`, `LEGACY_AVATAR_RSYNC_DEST` — див. `.env.example`.

## 2. Копіювання uploads (T132)

```bash
cd backend && php artisan chat:legacy-sync-uploads --dry-run
php artisan chat:legacy-sync-uploads
```

Змінні: `LEGACY_UPLOADS_RSYNC_SOURCE`, `LEGACY_UPLOADS_RSYNC_DEST` — див. `.env.example`.

**Перед прогоном:** перевірте вільне місце (`df -h`), права на каталог призначення, `chown` під веб-сервер після копіювання.

**Відсутні файли:** rsync покаже список; імпорт БД не падає — у UI можливі «биті» картинки до ручного виправлення.

## 3. Ремап URL у БД

Після того як файли доступні за новим публічним базовим URL, оновіть посилання в текстах:

1. Задайте **`LEGACY_URL_REMAP_TARGET_ORIGIN`** (без завершального `/`), наприклад `https://chat.example.com`.
2. Зробіть **бекап** таблиць `chat` і `private_messages` (або повної БД).
3. Сухий прогін:

```bash
php artisan chat:legacy-remap-board-urls --dry-run
```

4. Реальний прогін (на production лише з **`--force`** і рішенням оператора):

```bash
php artisan chat:legacy-remap-board-urls --force
```

**Що змінюється:** колонки **`chat.post_message`**, **`chat.avatar`**, **`private_messages.body`**.  
**Whitelist джерел:** префікси `http(s)://board.te.ua`, `http(s)://www.board.te.ua`, `//board.te.ua`, `//www.board.te.ua` → заміна на `LEGACY_URL_REMAP_TARGET_ORIGIN` (ідемпотентно для повторного прогону з тим самим target).

**Зображення в `images` / `avatar_image_id`:** не змінюються цією командою; прив’язка файлів — окремі кроки продукту (T10/T18/T113).

## QA

- `php artisan test` — `LegacyBoardUrlRemapTest`, `ChatLegacyCommandsTest` (конфіг).
- Після прогону: вибірка повідомлень з картинками в UI стрічки та аватарки профілю.
