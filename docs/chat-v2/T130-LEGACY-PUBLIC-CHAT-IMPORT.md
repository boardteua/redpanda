# T130 — Імпорт публічного чату (legacy → redpanda)

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) — **T130**.  
**Runbook:** [T128-LEGACY-PROD-IMPORT-RUNBOOK.md](T128-LEGACY-PROD-IMPORT-RUNBOOK.md).  
**Staging-огляд:** [T13-ETL-STAGING.md](T13-ETL-STAGING.md).

## Політика цільової БД

- **Один канонічний варіант (поточна реалізація):** імпорт дозволено лише якщо таблиці **`users`**, **`rooms`**, **`chat`** у **цільовій** (app) БД **порожні**. Інакше `LegacyBoardImportService` кидає виняток.
- **Merge / повторний прогін по `legacy_id`:** не реалізовано; потребує окремого специфікації та міграцій (конфлікти `post_id`, користувачі).

## Команди

| Команда | Призначення |
|--------|-------------|
| `php artisan chat:legacy-import-staging …` | Staging / експерименти (**T13**) |
| `php artisan chat:legacy-import-production …` | Той самий пайплайн для **production** за **T128** (потрібні `--force` у prod і рішення оператора) |

Обидві підтримують **`--dry-run`** та **`--force`** (заборона випадкового prod-запуску без `--force`).

## Мапінг legacy → redpanda

Реалізація: `backend/app/Services/LegacyBoardImport/LegacyBoardImportService.php`.

| Legacy (`org100h`) | Redpanda | Примітки |
|--------------------|----------|----------|
| `rooms.*` | `rooms` | `room_id`, `room_name`, `topic`, `access`; `created_by_user_id` = null |
| `users` (фільтр T113) | `users` | Імпортуються лише користувачі з ≥1 рядком у legacy `chat`; `legacy_imported_at` для не-гостей (**T129**) |
| `chat.*` | `chat` | `post_id`, `user_id`, `post_date`, `post_time`, `post_user`, `post_message`, …; **`file` = 0** (**T13**) |
| — | `chat.client_message_id` | **Стабільний** детермінований UUID-подібний рядок від `md5('redpanda:legacy:chat:'.$post_id)` (формат 8-4-4-4-12) |
| Відсутній `users.user_id` для автора в `chat` | `users` (stub) | `guest=true`, `legacy_imported_at` = null, нік `legacy_uid_{id}` |

Відбір користувачів: `LegacyImportUserSelection::usersHavingPublicChatPosts` (**T113**).

## Ідемпотентність `client_message_id`

- Для кожного legacy `post_id` значення **завжди однакове** при повторному обчисленні.
- Повторний **INSERT** того ж `post_id` в одну БД заборонений PK `post_id`; повторний прогін у **порожню** схему дає ті самі `client_message_id`, що зручно для звірки з клієнтськими логами.

## Порядок відносно T129 / T131

1. **T129** — користувачі (і стаби) мають існувати до або разом з імпортом `chat` згідно runbook (**T128**). Поточний сервіс імпортує **rooms → users → stubs → chat** в одній транзакції пайплайну.
2. **T131** — приватні повідомлення після публічного чату та стабільних `users.id`.
