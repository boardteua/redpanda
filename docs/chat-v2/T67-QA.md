# T67 — Slash-команди: звичайний користувач і гість — QA

**Вердикт:** PASS

## Доказ

- **PHPUnit:** `tests/Feature/ChatApiTest.php` — `test_slash_manual_is_client_only_and_recognized`, `test_slash_friend_forbidden_for_guest`, `test_slash_friend_creates_pending_friendship`, `test_slash_seen_shows_last_message_in_room`, `test_slash_away_dispatches_presence_status_updated`, `test_slash_msg_without_peer_returns_422`; існуючі кейси T66 (`/me`, невідома команда, інлайн `/msg`).
- **Повний набір:** `php artisan test` — PASS.
- **Фронт:** `npm run build` у `backend/` — PASS.

## Що перевірено

- `/manual` — `type: client_only`, `meta.slash.recognized: true`, текст містить перелік команд; **гість** може.
- `/friend` — **гість** отримує **403**; зареєстрований створює `friendships` зі статусом `pending` і client_only-відповідь.
- `/seen нік` — client_only, рядок про останнє повідомлення в кімнаті (публічне або інлайн-приват).
- `/away` — client_only + `BroadcastEvent` з `PresenceStatusUpdated` (away).
- `/msg` без ніка — **422** (підказка); `/msg нік текст` лишається шляхом **RoomInlinePrivateParser** (до slash-реєстру не доходить).

## Примітки

- Модераторські та кімнатні команди — **T68–T71**.
