# T72 — QA evidence (slash `/addtheme`, `/deltheme`)

**Вердикт:** PASS

## Автоматичні перевірки

- `php artisan migrate --no-interaction` — застосовано міграцію `2026_03_24_180000_create_chat_themes_table`.
- `php artisan test` — усі тести зелені, зокрема `AdminSlashCommandsApiTest`: заборона для не-адміна, успішне додавання рядка в `chat_themes`, **422** на дублікат назви, видалення через `/deltheme` з іншим регістром.

## Поведінка (узгоджено з задачею)

- Каталог зберігається в **`chat_themes`** (`name`, `sort_order`); відповідає духу legacy **`theme`** у `DATABASE-SCHEMA.md`.
- Команди зареєстровані в **`SlashCommandRegistry`**; **`can:chat-admin`** (як інші адмінські slash з T71).
- Довідник **`/manual`** оновлено; **`docs/chat-v2/openapi.yaml`** — опис T72 у `POST …/rooms/{id}/messages`.
- Вибір теми в UI не входить у T72 — лише CRUD каталогу через slash + публічний рядок у стрічці.

## Примітка для деплою

- На існуючій БД виконати міграції; таблиця може бути порожньою до першого `/addtheme`.
