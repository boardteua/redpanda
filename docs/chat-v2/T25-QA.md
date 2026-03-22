# T25 — QA (стрічка: згадка по ніку, інлайн `/msg`, приватність пари)

## Реалізація

- **Бекенд:** повідомлення з префіксом `/msg нік …` у `POST /api/v1/rooms/{room}/messages` зберігаються як `type: inline_private`, `post_target` = id адресата; **у тій самій транзакції** дублюється запис у `private_messages` (той самий `client_message_id`, тіло після фільтра), щоб вкладка «Приват» / API розмов узгоджувалися з інлайн-стрічкою. Конфлікт `client_message_id`, уже зайнятого в `private_messages` (наприклад надісланий через `POST …/private/peers/…/messages`), дає **422**. Фільтр у `GET` історії кімнати — лише `public` або `inline_private`, де поточний користувач є відправником або адресатом; архів показує лише `type: public`. Трансляції: `PrivateMessageCreated` (як у панелі привату) + `RoomInlinePrivatePosted` на **два** канали `user.{sender_id}` та `user.{recipient_id}` (без `MessagePosted` у `room.*`). Парсер: `App\Chat\RoomInlinePrivateParser`.
- **Ресурс:** `ChatMessageResource` — поле `recipient_user_id` для `inline_private`.
- **Фронт:** у стрічці клік по **ніку** вставляє `нік > ` у композер (без контекстного меню T22); клік по **аватарці** іншого автора — `/msg нік `; власний рядок без кнопки на аватарі. Відправка `/msg` йде тим самим `POST` у кімнату (без відкриття панелі привату). Echo: слухач `.RoomInlinePrivatePosted` на `user.{id}` + `mergeMessage`. Стиль рядка: клас `rp-chat-feed-row--inline-private` + токени `--rp-chat-row-inline-private-*` у `app.css`.
- **OpenAPI:** оновлено опис `ChatMessage.type` та `recipient_user_id`.

## Автоматичні перевірки

```text
cd backend && php artisan test --filter='ChatApiTest|ChatArchiveApiTest::test_archive_excludes_inline_private'
cd backend && npm run build
```

## Ручний сценарій (опційно)

Два акаунти A і B та третій C у тій самій кімнаті: A надсилає з композера `/msg B_нік тест` — A і B бачать рядок у стрічці з виділенням; C не бачить. Клік по ніку B → у полі з’являється `B_нік > ` без меню; клік по аватарці B → `/msg B_нік `.

## Вердикт

**QA PASS** для T25 за результатами PHPUnit (у т.ч. ізоляція третьої сторони, broadcast, архів) та `npm run build`.
