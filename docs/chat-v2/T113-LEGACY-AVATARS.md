# T113 — Legacy ETL: користувачі лише з публічним чатом + аватарки

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) — T113.

## 1. Фільтр користувачів при імпорті (`chat:legacy-import-staging`)

У **цільову** схему redpanda потрапляють лише рядки з **legacy `users`**, для яких існує **хоча б один** рядок у **legacy `chat`** з тим самим `user_id` (публічні повідомлення в кімнатах).

- Користувачі **без** жодного такого рядка **не вставляються**; їх кількість показує **`chat:legacy-inspect`** (блок T113) і **`--dry-run`** імпорту (`users пропущено`).
- Рядки **`chat`** для «відсутніх» user_id у цільовій БД як і раніше потрапляють у **`chat (пропущено)`**, якщо user не існує після фільтра (для нормального дампу таких ситуацій не повинно бути, окрім уже описаних сиріт/stub).

### Облік лише `chat`, не `private`

Критерій **наразі** — таблиця **`chat`** (публічна стрічка). Якщо продукт вирішить враховувати також наявність повідомлень у **`private`**, це окреме уточнення: розширити підзапит у `LegacyBoardImportService` / `LegacyImportUserSelection` і оновити цей документ.

## 2. Синхронізація файлів аватарок з board.te.ua

На legacy-хості файли зазвичай лежать у каталозі на кшталт **`/var/www/board.te.ua/html/avatar/`**; ім’я файлу відповідає **нікнейму** (`user_name`). Реєстр файлів на диску може не збігатися з регістром — узгоджуйте з фактичною ФС на сервері.

### Підготовка

1. SSH-доступ до хоста **без пароля в репозиторії**: ключ у `ssh-agent` або `~/.ssh`, користувач з правом **читання** каталогу аватарок.
2. Локальний (або staging) каталог призначення, наприклад `storage/app/legacy-avatars/` у проєкті redpanda — **поза git** або в `.gitignore`, якщо туди кладуться копії.

### Змінні `.env`

Див. **`backend/.env.example`**: `LEGACY_AVATAR_RSYNC_SOURCE`, `LEGACY_AVATAR_RSYNC_DEST`.

Приклад (значення не комітити):

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

`--dry-run` передає **`rsync -n`** (лише список змін). Використовується **`ssh -o BatchMode=yes`** (без інтерактивного пароля в CI/скриптах).

### Після копіювання

Прив’язка файлу до **`users.avatar_image_id`** / таблиці **`images`** (дух T10/T18/T19) — окремий крок продукту: імпорт метаданих або одноразовий скрипт за `user_name` → шлях файлу. Відсутній файл на дискі **не повинен** ламати імпорт БД: поле залишається `null`.

## 3. QA

[docs/chat-v2/T113-QA.md](T113-QA.md).
