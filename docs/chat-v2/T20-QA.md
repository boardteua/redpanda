# T20 — QA (Presence, учасники онлайн)

## Рішення (узгодження стеку)

Обрано **Laravel PresenceChannel + Echo.join** для каналу `room.{roomId}`: одна WS-підписка одночасно несе **`.MessagePosted`** і **список присутніх** (`here` / `joining` / `leaving`). TTL «онлайн» = час активного підключення до Reverb для цієї кімнати; **окремий HTTP heartbeat у Redis не впроваджувався** (менше рухомих частин, узгоджено з уже наявним Reverb з T05). Дані в `channel_data` обмежені тим, що вже видно в стрічці (нік, гість, URL аватара з T18).

## Автоматичні тести

```bash
cd backend && php artisan test --filter=BroadcastChannelTest
cd backend && php artisan test --filter=ChatApiTest
```

Очікування:

- `BroadcastChannelTest` — авторизація **`presence-room.{id}`** з `auth` + `channel_data`; заборона без сесії / чужої кімнати / registered-only для гостя.
- `ChatApiTest::test_message_posted_broadcasts_on_presence_room_channel` — `MessagePosted` транслюється на **PresenceChannel** `presence-room.{id}`.

## Ручний сценарій (два клієнти)

1. `BROADCAST_CONNECTION=reverb`, `php artisan reverb:start`, `php artisan serve` (+ queue worker, якщо черга не `sync`).
2. Два браузери (або вікна інкогніто): увійти як різні користувачі, та сама публічна кімната.
3. Вкладка сайдбару **Люди**: кожен бачить себе та іншого в блоці «інші онлайн»; після закриття вкладки одного — інший у межах кількох секунд бачить оновлення списку.
4. У разі деградації до poll (банер у шапці) — показується пояснення, що список інших онлайн недоступний.

## Вердикт

- **PASS (автоматичні):** `php artisan test` — 66 тестів OK; `npm run build` — OK (2026-03-22).
- **Ручний сценарій** (два клієнти, Reverb) — за потреби підтвердити оператором; логіка Echo `here` / `joining` / `leaving` узгоджена з `laravel-echo` (Pusher: у колбеки передається `user_info` з полями з `routes/channels.php`, включно з `id`).
