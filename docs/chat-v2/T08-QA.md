# T08 — QA (приват, друзі, ігнор)

## Backend

- Таблиці: `private_messages`, `friendships`, `user_ignores` (міграції).
- **Приват:** `GET /api/v1/private/conversations`, `GET /api/v1/private/peers/{id}/messages` (cursor `before` = `id`), `POST .../messages` з `message` + `client_message_id` (idempotency як у публічного чату).
- **Lookup:** `GET /api/v1/users/lookup?name=` — нік для `/msg` і UI.
- **Друзі:** `GET /api/v1/friends`, `.../requests/incoming`, `.../requests/outgoing`, `POST /friends/{id}`, `POST .../accept`, `POST .../reject`.
- **Ігнор:** `GET /api/v1/ignores`, `POST /ignores/{id}`, `DELETE /ignores/{id}` — блокує приват у **обидва** боки.
- **Broadcast:** `PrivateMessageCreated` → **`.PrivateMessagePosted`** на `private-user.{recipientId}` (канал `user.{id}` у `routes/channels.php`).

## Фронт (`/chat`)

- Плаваюча **панель привату** (`PrivateChatPanel.vue`): історія, композер, один шлях `POST private/peers/...`.
- Вкладка **Приват** — список тредів з `conversations`; **Друзі** — активні / вхідні-вихідні запити; **Ігнор** — список + «Зняти».
- **Люди:** поле «Приват за ніком»; у стрічці клік по **нікнейму** відкриває приват.
- У **загальному** композері: **`/msg нік`** або **`/msg нік текст`** — lookup + відкриття привату (і відправка, якщо є текст).
- Echo: слухач **`.PrivateMessagePosted`** на `private('user.'+authId)`; мердж у відкритий тред + оновлення списку розмов.

## Автотести

```bash
cd backend && php artisan test --filter=PrivateMessageApiTest
cd backend && php artisan test --filter=FriendApiTest
cd backend && php artisan test --filter=IgnoreApiTest
cd backend && npm run build
```

## Ручний сценарій

1. Два користувачі в одній кімнаті; **А** відкриває приват до **Б** (нік або список).
2. **А** надсилає повідомлення; **Б** бачить у панелі / списку; за наявності Reverb — миттєво через WS.
3. **Б** додає **А** в ігнор — жоден не може писати в приват (403).
4. Запит у друзі → вхідний → прийняти → обидва в списку друзів.

## Скріншоти

За потреби: `docs/chat-v2/screenshots/t08/private-panel.png`, `friends-incoming.png`.
