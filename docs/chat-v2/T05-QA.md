# T05 — QA (Reverb + broadcast)

## Автоматичні тести

```bash
cd backend && php artisan test --filter=BroadcastChannelTest
cd backend && php artisan test --filter=test_post_dispatches_broadcast_only_for_new_message
```

Очікування:

- **`BroadcastChannelTest`** — без сесії `POST /broadcasting/auth` → **403**; гість не підписується на **`presence-room.{id}`** з `access > 0`; гість/зареєстрований — успіх там, де дозволяє `RoomPolicy::interact` (у відповіді presence є `channel_data`); `private-user.{id}` лише для власного `id`.
- **`ChatApiTest::test_post_dispatches_broadcast_only_for_new_message`** — після першого POST ставиться в чергу `BroadcastEvent` з `MessagePosted`; ідемпотентний повтор **не** додає другий broadcast.

## Локальний ручний чек (опційно)

1. У `.env`: `BROADCAST_CONNECTION=reverb`, заповнені `REVERB_*`, `php artisan reverb:install` за потреби.
2. Термінал 1: `php artisan reverb:start`
3. Термінал 2: `php artisan serve` (+ за потреби `php artisan queue:listen`, якщо `QUEUE_CONNECTION` не `sync`).
4. Клієнт Echo (T06/T20): підписка **`Echo.join('room.' + roomId)`** → канал **`presence-room.{roomId}`** (подія **`.MessagePosted`** + presence `here` / `joining` / `leaving` для списку онлайн у сайдбарі).

## Подія `MessagePosted`

- Канал: **`presence-room.{post_roomid}`** (T20; раніше був `private-room.*`).
- Ім’я події для клієнта: **`MessagePosted`**.
- Payload: мінімальні поля повідомлення (`post_id`, `post_roomid`, `user_id`, `post_date`, `post_user`, `post_message`, …) — див. `App\Events\MessagePosted::broadcastWith`.
- Після успішного створення повідомлення викликається `broadcast(...)->toOthers()` (відправник з тим самим `socket_id` не отримує дубль з WS, якщо клієнт передає `X-Socket-ID`).

## Черга та доставка WS

- `MessagePosted` реалізує **`ShouldBroadcast`** — у проді подія потрапляє в **чергу** (`BroadcastEvent`). Якщо `QUEUE_CONNECTION=database` (як у `.env.example`) і **немає** `php artisan queue:listen` / воркера, **Reverb не отримає** подію (HTTP POST у чат працює, інші клієнти не бачать push).
- Для локальних тестів у `phpunit.xml` стоїть **`QUEUE_CONNECTION=sync`** — broadcast виконується в тому ж процесі.

## Безпека payload (фронт)

- `post_message` та `post_user` у WS так само **не санітизовані** сервером. UI (T06) має показувати їх як **текст** (`{{ }}` у Vue), не як HTML.
