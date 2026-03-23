# T68 — Slash-команди: ігнор і приват — QA

**Вердикт:** PASS

## Доказ

- **PHPUnit:** `tests/Feature/ChatApiTest.php` — `test_slash_ignore_forbidden_for_guest`, `test_slash_ignore_adds_to_ignore_list`, `test_slash_ignoreclear_removes_all_ignores_for_user`; `tests/Feature/PrivateMessageApiTest.php` — `test_destroy_thread_clears_messages_read_state_and_dispatches_event`, `test_destroy_thread_forbidden_when_pair_is_blocked_by_ignore`.
- **Повний набір:** `php artisan test` — PASS.
- **Фронт:** `npm run build` у `backend/` — PASS.

## Що перевірено

- `/ignore нік` і `/ignoreclear` у кімнаті через slash-реєстр: **гість** → **403**; зареєстрований — запис у `user_ignores` або масове видалення своїх ігнорів, відповідь **client_only** з `meta.slash`.
- **`DELETE /api/v1/private/peers/{peer}/thread`:** видаляє всі `private_messages` між парою, очищає `private_message_read_states` для цієї пари; **403**, якщо пара в ігнорі (`PrivateMessageGate`); подія **`PrivateThreadCleared`** відправляється.
- **Vue:** у приватній панелі рядок **`/clear`** (лише ця команда) викликає `DELETE …/thread`; слухач **`.PrivateThreadCleared`** очищає відкритий тред і оновлює `loadConversations`.

## Примітки

- **`/clear` у загальному чаті кімнати** не реєструється тут — планується в **T70** (журнал кімнати).
