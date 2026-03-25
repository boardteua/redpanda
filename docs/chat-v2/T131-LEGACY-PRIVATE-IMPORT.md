# T131 — Імпорт приватних повідомлень (`private` → `private_messages`)

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) — **T131**.  
**Runbook:** [T128-LEGACY-PROD-IMPORT-RUNBOOK.md](T128-LEGACY-PROD-IMPORT-RUNBOOK.md).  
**Специфіка UI legacy:** [PRIVATE-MESSAGES.md](../board-te-ua/PRIVATE-MESSAGES.md).

## Політика цільової БД

- Імпорт дозволено лише в **порожню** таблицю **`private_messages`**. Повторний merge за legacy-ключем — не реалізовано.

## Команда

```bash
php artisan chat:legacy-import-private --dry-run
php artisan chat:legacy-import-private --force   # на production лише з рішенням оператора
```

Передумови: **`LEGACY_DB_*`**, таблиця **`private`** у legacy; у **app** БД вже імпортовані **`users`** з тими ж `user_name`, що й ніки в legacy (**T129**/**T130**).

## Мапінг полів

Реалізація: `backend/app/Services/LegacyBoardImport/LegacyPrivateMessageImportService.php`.

| Legacy `private` | `private_messages` | Примітки |
|------------------|-------------------|----------|
| `hunter` (нік) | `sender_id` | Пошук `users.user_name` **без урахування регістру** (нормалізація до lower); перший збіг за алфавітом id при колізії імен (рідко) |
| `target` (нік) | `recipient_id` | Аналогічно |
| `message` | `body` | Порожній рядок після trim — пропуск |
| `time` або `date` | `sent_at` | Unix time; якщо ≤ 0 — пропуск |
| `id` | — | Детермінований `client_message_id` = UUID-подібний рядок від `md5('redpanda:legacy:private:'.$id)` |
| `display_time` (опційно) | `sent_time` | Якщо колонка існує в дампі |

Рядки з однаковим `hunter` і `target` (self) пропускаються.

## Сироти

- Немає `users` для ніка відправника або отримувача → лічильники `skipped_no_hunter` / `skipped_no_target`.

## Read / unread (`private_message_read_states`)

- У цій версії **не** заповнюється: семантика legacy поля `view` / статусів не уніфікована в усіх дампах. Після імпорту непрочитані можуть відображатися як «усі нові» до першого відкриття треду. Окремий бекфіл — за потреби продукту.

## QA

- PHPUnit: `tests/Feature/LegacyPrivateImportTest.php` (sqlite legacy in-memory + два користувачі).
- Після прогону на копії: dry-run показує `legacy_rows`; ручний smoke — два акаунти бачать історію.
