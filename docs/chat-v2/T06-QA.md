# T06 — QA (Vue чат + Echo)

## Функціонал

- Маршрут **`/chat`** (Vue 2.7 + vue-router): стрічка повідомлень, вибір кімнати, композер.
- Після відправки POST відповідь **мерджиться** у стрічку; подія **`.MessagePosted`** з Echo — дедуп по **`post_id`**.
- Якщо немає `VITE_REVERB_APP_KEY` або підписка на канал падає — індикатор **«Реалтайм недоступний»** і **опитування** `GET .../messages` кожні **10 с** (дедуп той самий).
- Текст повідомлення ренериться через **mustache** (`{{ post_message }}`) — екранування HTML.

## Автоматичні тести

```bash
cd backend && php artisan test --filter=SpaShellTest
```

## Ручний сценарій

1. `php artisan serve`, `php artisan reverb:start`, `LARAVEL_BYPASS_ENV_CHECK=1 npm run dev` (за потреби), **`php artisan queue:listen`** якщо черга не `sync`.
2. Увійти на `/`, натиснути **«Відкрити чат»** → `/chat`.
3. Надіслати повідомлення; у другому браузері/інкогніто — те саме кімната — побачити через WS або після poll.

## Скріншоти

Додайте під цим розділом (за потреби): `docs/chat-v2/screenshots/t06/chat-desktop.png`, `chat-narrow.png`.
