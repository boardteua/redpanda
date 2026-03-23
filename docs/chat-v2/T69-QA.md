# T69 — Slash-команди: модератор кімнати — QA

**Вердикт:** PASS

## Доказ

- **PHPUnit:** `tests/Feature/ChatApiTest.php` — `test_slash_mute_forbidden_for_regular_user`, `test_slash_mute_mod_applies_default_minutes`, `test_slash_unmute_clears_mute`, `test_slash_mod_cannot_target_admin`, `test_slash_ban_mod_forbidden`, `test_slash_ban_admin_disables_account`, `test_slash_upoff_blocks_chat_image_upload` (цикл /upoff + /upon); `tests/Feature/ChatImageApiTest.php` — `test_registered_user_cannot_upload_when_chat_upload_disabled`.
- **Повний набір:** `php artisan test` — PASS.
- **Фронт:** `npm run build` у `backend/` — PASS.

## Що перевірено

- Реєстр: `/mute`, `/kick`, `/unmute`, `/upon`, `/upoff`, `/ban` — узгоджено з **`ModerationService`**, **`canReceiveStaffManagementFrom`**, **`POST /api/v1/mod/users/{id}/mute|kick`** (mute/kick/unmute/upon/upoff); **`/ban`** — **`account_disabled_at`**, **лише адмін** (`isChatAdmin()`).
- **`chat_upload_disabled`:** блокує **`POST /api/v1/images`**, **`POST /api/v1/me/avatar`** і **`image_id`** у **`POST …/rooms/{id}/messages`** (Form Request).
- Відповіді успіху — **`type: client_only`**, **`meta.slash`** з ім’ям команди.

## Примітки

- Другий аргумент для **`/mute`** / **`/kick`**: цілі хвилини або **`0`** для зняття; без аргумента — дефолти з **`config/chat.php`** (`slash_mod_default_*`).
