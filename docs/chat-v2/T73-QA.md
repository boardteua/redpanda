# T73 — Менеджер slash-команд з реєстрацією callback — QA

**Вердикт:** PASS

## Доказ

- **PHPUnit:** `backend/tests/Feature/ChatApiTest.php` → `test_slash_command_can_be_registered_with_callable_handler`  
  Команда: `cd backend && php artisan test --filter=test_slash_command_can_be_registered_with_callable_handler`
- **Збірка фронту:** не змінювалась у цій задачі (task scope — backend registry).

## Що перевірено

- Slash-команда може бути зареєстрована **в коді** (без БД / без виконання “зовнішнього” PHP) через `SlashCommandRegistry::register()` з **callable handler**.
- Запит `POST /api/v1/rooms/{room}/messages` з `/testcb ...` проходить повний шлях парсингу → реєстру → обробника.
- Відповідь має тип `client_only`, а `meta.slash` містить коректні поля (`name`, `recognized`, `client_only`).

## Документація

- Як додавати нові slash-команди через менеджер: `docs/chat-v2/slash-commands-manager.md`.

