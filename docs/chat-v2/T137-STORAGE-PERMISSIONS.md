# T137 — Права на `storage/app/chat-images` (upload у чат / аватар)

Операційний runbook для **staging / production**, коли завантаження зображень падає через **права на диск**, а не через ліміти nginx/PHP або валідацію файлу.

## Симптом

- У UI або в відповіді API з’являється повідомлення на кшталт: *«Не вдалося зберегти файл на сервері. Перевірте права на каталог storage/app/chat-images…»* (код **503** на `POST /api/v1/images` або відповідному ендпоінті аватара).
- У `storage/logs/laravel.log` можуть бути записи з контекстом `chat_image_store_failed` / `avatar_image_*` або системні **`Permission denied`** при записі в дерево **`storage/app/chat-images`**.

Пов’язані задачі: **T98** (повідомлення клієнту), **T10**/**T18** (шлях збереження), **T113**/**T132** (імпорт legacy — змішані власники після `rsync`).

## RCA (типова причина)

1. **Змішані власники** після копіювання даних з іншого хоста або під іншим користувачем деплою (наприклад, файли **`www-data:www-data`**, а нові — під **`deploy_user:deploy_user`**).
2. Каталог з **`chmod 775`**, але **ефективний користувач** PHP-FPM або **queue worker** **не** входить у групу-власника каталогу — тоді запис у каталог, власник якого **`www-data`**, від імені іншого користувача **не** дозволений навіть при «нормальному» вигляді прав зверху.
3. Після імпорту перевіряли лише батьківський **`chat-images`**, а **вкладені** `chat-images/{user_id}/…` лишилися з іншим власником — запис у підкаталог все одно падає.

**Не плутати** з: **`client_max_body_size`** (nginx), **`upload_max_filesize`** / **`post_max_size`** (PHP) — тоді запит часто не доходить до Laravel; див. рядок у [T80-DEPLOY-CHECKLIST.md](T80-DEPLOY-CHECKLIST.md) (Fail-fast).

## Хто має писати на диск

Потрібно узгодити **одного** «канонічного» користувача для:

- процесу, що обробляє **`POST /api/v1/images`** і **`POST …/me/avatar`** (зазвичай **PHP-FPM pool**);
- **`php artisan queue:work`**, якщо обробка upload або пов’язаних job торкається того ж диска (у цьому проєкті основний запис у контролерах — через HTTP, але воркер теж має мати узгоджений доступ до **`storage/`**, **`bootstrap/cache`** тощо).

### Діагностика: ефективний користувач

**Класичний VPS (PHP-FPM на хості):**

- Подивитися **`user=`** / **`group=`** у pool (наприклад, **`/etc/php/8.3/fpm/pool.d/www.conf`** або вашому pool).
- Або: процес, що обслуговує FPM, і користувач воркера черги: `ps aux | grep php-fpm`, `ps aux | grep queue:work`.

**Docker (маніфест `docker/compose.yaml`):**

- Образ **`php:8.3-fpm-bookworm`** за замовчуванням працює від **`www-data`** усередині контейнера **`php`** / **`queue`** / **`reverb`**, якщо Dockerfile не змінює `USER`.
- Перевірка всередині контейнера, де виконується запит або Artisan:

```bash
docker compose -f docker/compose.yaml --profile app exec php id
docker compose -f docker/compose.yaml --profile app exec queue id
```

Якщо **`backend/`** змонтовано з хоста (**bind mount**), **UID/GID на файлах** у томі відповідають хосту: після `chown` на хості всередині контейнера видно той самий числовий uid/gid.

## Перевірка ланцюга каталогів і власників

Переконатися, що **кожен** компонент шляху дозволяє прохід (**execute** для каталогів) потрібному користувачу:

```bash
namei -l /шлях/до/backend/storage/app/chat-images
```

Переглянути права на дерево (приклад; підставте реальний **`$APP_ROOT/backend`**):

```bash
ls -la "$APP_ROOT/backend/storage/app/chat-images"
find "$APP_ROOT/backend/storage/app/chat-images" -maxdepth 3 -type d -ls 2>/dev/null | head
```

Після успішного upload — що файл створено очікуваним власником:

```bash
ls -la "$APP_ROOT/backend/storage/app/chat-images/<user_id>/"
```

У логах не повинно з’являтися **`Permission denied`** для шляхів під **`storage/app/chat-images`**.

## Виправлення (шаблони; без `chmod 777` на проді)

Оберіть **одну** узгоджену політику для команди.

### Варіант A — все під користувача веб-стеку (найпростіший)

Якщо FPM і воркер працюють як **`www-data`**:

```bash
sudo chown -R www-data:www-data /шлях/до/backend/storage/app/chat-images
sudo chown -R www-data:www-data /шлях/до/backend/storage/framework /шлях/до/backend/storage/logs
sudo chown -R www-data:www-data /шлях/до/backend/bootstrap/cache
```

За потреби каталоги **775**, файли **664** (або політика umask вашого деплою). Після `rsync` з іншого сервера — **рекурсивно** пройти **`chat-images`** і підкаталоги імпорту (**legacy-avatars**, **legacy-uploads** тощо), якщо вони на тому ж диску.

### Варіант B — спільна група + setgid на каталогах

Якщо деплой і PHP мають бути в одній групі (наприклад, **`redpanda`**):

- `chgrp -R redpanda storage/app/chat-images` (і за потреби інші дерева `storage/`).
- На каталогах: **`chmod g+s`** (setgid), щоб нові підкаталоги успадковували групу.
- Переконатися, що в pool FPM **`group=`** відповідає цій групі або користувач у неї входить.

### Варіант C — ACL

Точково додати права на запис для користувача воркера/FPM без зміни основного власника — за політикою безпеки хоста (`setfacl`).

## Docker: після імпорту томів

Якщо на хості під root/rsync з’явилися файли з **uid root** або іншим uid, контейнерний **`www-data`** може їх не записувати. Вирівняйте власника на **хості** для змонтованого **`backend/`** (числовий uid/gid **`www-data`** у контейнері можна подивитися через `docker compose exec php id` і відобразити на хост через `chown` numeric).

## Посилання в коді

- Диск **`chat_images`**: `backend/config/filesystems.php` → root **`storage_path('app/chat-images')`**.
- Створення батьківського каталогу при старті: `backend/app/Providers/AppServiceProvider.php` — `File::ensureDirectoryExists(storage_path('app/chat-images'))`.
- Повідомлення про помилку збереження: `ChatImageController`, `UserAvatarController`.

## QA (приймання після змін на сервері)

1. **`POST /api/v1/images`** (multipart, валідне зображення) — **200/201**, файл на диску, очікуваний власник.
2. Завантаження аватара в UI — успіх.
3. Артефакт для внутрішнього логу: вивід **`namei -l`** або **`ls -la`** по шляху після upload + фрагмент **`laravel.log`** без **`Permission denied`** для **`chat_image_*`**.

Локально в CI тести використовують **`Storage::fake('chat_images')`** — вони не замінюють перевірку прав на реальному томі.
