# T30 — QA: форматування повідомлень (B/I/U, тло, колір тексту)

## Зміни (коротко)

- **БД:** колонка `chat.post_style` (JSON, nullable) — пресети без HTML від клієнта.
- **API:** опційне поле `style` у `POST /api/v1/rooms/{roomId}/messages`; відповіді та WS містять `post_style`. `bg` і `fg` взаємовиключні (422 при обох).
- **Бекенд:** `App\Support\ChatMessageBodyStyle`, валідація в `StoreChatMessageRequest`, санітизація = лише дозволені ключі.
- **Фронт:** тулбар у `ChatRoom.vue` (палітри тла/тексту, тогли B/I/U), «липкий» стан у `localStorage` (`rp_chat_composer_style_v1`), рендер у стрічці та в `ArchiveChat.vue` через класи з `app.css`.
- **OpenAPI:** `ChatMessageBodyStyle`, оновлені `ChatMessage` та `PostChatMessageRequest`.

## Автоматичні перевірки

- `php artisan test` — **96 passed** (у т.ч. `test_post_accepts_message_style_and_returns_in_feed`, `test_post_rejects_invalid_message_style_bg`, `test_post_rejects_style_with_both_bg_and_fg`).
- `npm run build` — **PASS**.

## Ручний чекліст (опційно для оператора)

- Увімкнути B + фон «amber», надіслати — у стрічці текст із жирним і підсвіченим чіпом; оновити сторінку — стиль з БД лишається.
- Вибрати колір тексту (без тла) — фон-палітра вимкнена; після зняття тла — палітра тексту знову доступна.

**Статус:** PASS
