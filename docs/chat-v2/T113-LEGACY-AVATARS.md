# T113 — Legacy ETL: користувачі лише з публічним чатом + аватарки

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) — T113.

## 1. Фільтр користувачів при імпорті (`chat:legacy-import-staging`)

У **цільову** схему redpanda потрапляють лише рядки з **legacy `users`**, для яких існує **хоча б один** рядок у **legacy `chat`** з тим самим `user_id` (публічні повідомлення в кімнатах).

- Користувачі **без** жодного такого рядка **не вставляються**; їх кількість показує **`chat:legacy-inspect`** (блок T113) і **`--dry-run`** імпорту (`users пропущено`).
- Рядки **`chat`** без відповідного **`user_id` у legacy `users`** не імпортуються; рядки для користувачів, відфільтрованих T113 (немає публічних постів), також потрапляють у **`chat (пропущено)`** у цільовій БД.

### Облік лише `chat`, не `private`

Критерій **наразі** — таблиця **`chat`** (публічна стрічка). Якщо продукт вирішить враховувати також наявність повідомлень у **`private`**, це окреме уточнення: розширити підзапит у `LegacyBoardImportService` / `LegacyImportUserSelection` і оновити цей документ.

## 2. Синхронізація файлів аватарок з board.te.ua

На legacy-хості файли зазвичай лежать у каталозі на кшталт **`/var/www/board.te.ua/html/avatar/`**; ім’я файлу відповідає **нікнейму** (`user_name`). Реєстр файлів на диску може не збігатися з регістром — узгоджуйте з фактичною ФС на сервері.

### Підготовка

1. Каталог призначення у redpanda, наприклад `storage/app/legacy-avatars/` — **поза git** / у `.gitignore`.
2. **Один сервер** (типово board.te.ua: legacy і redpanda на диску поруч): у `.env` задаються **два абсолютні локальні шляхи** — команда викликає **`rsync` без SSH** (копіювання по ФС).
3. **Інший сервер** як джерело: формат **`user@host:/шлях/`** у `LEGACY_AVATAR_RSYNC_SOURCE` — тоді додається **`rsync -e ssh`** (ключ у `ssh-agent`, `BatchMode=yes`).

### Змінні `.env`

Див. **`backend/.env.example`**: `LEGACY_AVATAR_RSYNC_SOURCE`, `LEGACY_AVATAR_RSYNC_DEST`.

Приклад **локально на одному хості** (значення не комітити):

```env
LEGACY_AVATAR_RSYNC_SOURCE=/var/www/board.te.ua/html/avatar/
LEGACY_AVATAR_RSYNC_DEST=/var/www/redpanda/backend/storage/app/legacy-avatars
```

Приклад **з віддаленого хоста**:

```env
LEGACY_AVATAR_RSYNC_SOURCE=user@board.te.ua:/var/www/board.te.ua/html/avatar/
LEGACY_AVATAR_RSYNC_DEST=/var/www/redpanda/backend/storage/app/legacy-avatars
```

### Команда

З каталогу `backend/`:

```bash
php artisan chat:legacy-sync-avatars --dry-run
php artisan chat:legacy-sync-avatars
```

`--dry-run` передає **`rsync -n`**. Альтернатива без PHP: `cp -a /var/www/board.te.ua/html/avatar/. /var/www/redpanda/backend/storage/app/legacy-avatars/` (переконайтесь, що каталог призначення існує і права коректні).

### Після копіювання

Прив’язка файлів до **`users.avatar_image_id`** і таблиці **`images`** (диск **`chat_images`**, як при звичайному завантаженні аватара):

```bash
# з backend/ або через Docker: ./docker/link-legacy-user-avatars.sh
php artisan chat:legacy-link-user-avatars --dry-run
php artisan chat:legacy-link-user-avatars
# production:
php artisan chat:legacy-link-user-avatars --force
```

Команда бере каталог з **`LEGACY_AVATAR_RSYNC_DEST`** (або **`--dir=/шлях/до/каталогу`**), для кожного **не-гостя** з **`legacy_imported_at IS NOT NULL`** і **`avatar_image_id IS NULL`** шукає файл з іменем **`{user_name}.{gif|png|jpg|jpeg|webp}`** (порівняння **без урахування регістру** стема) і копіює зображення в **`storage/app/chat-images/{user_id}/avatars/`**, створює рядок **`images`**, оновлює користувача. Відсутній файл — поле лишається **`null`**.

## 3. QA

[docs/chat-v2/T113-QA.md](T113-QA.md).
