# T04 — QA (Chat REST v1)

Машиночитаний контракт: **[openapi.yaml](openapi.yaml)** (перегляд у Swagger UI / Redoc; `npx @redocly/cli lint docs/chat-v2/openapi.yaml`).

## Автоматичні тести

```bash
cd backend && php artisan test
```

Очікування: `ChatApiTest` — idempotency POST, 422 при повторному `client_message_id` в іншій кімнаті, 403 гостя в `access > 0`, валідація, cursor/history, `/me` у slash-pipeline.

## Браузер (local)

1. `APP_ENV=local`, `php artisan serve`, `LARAVEL_BYPASS_ENV_CHECK=1 npm run dev` (за потреби).
2. Відкрити **`http://127.0.0.1:8000/__qa/chat-api`**.
3. Натиснути **«Повний цикл»** або кроки 1→2→3→5.
4. Переконатися: після кроку 5 у логах є `201` і `meta.slash.recognized: true` (для `/me`) або `200` + `meta.duplicate: true` та `meta.slash.recognized: false` на повторі з тим самим UUID.

## API (підсумок)

| Метод | Шлях | Throttle |
|-------|------|----------|
| GET | `/api/v1/rooms` | `chat-read` (120/хв/користувач) |
| GET | `/api/v1/rooms/{room}/messages?before=&limit=` | те саме |
| POST | `/api/v1/rooms/{room}/messages` | `chat-post` (30/хв/користувач) |

Тіло POST: `message` (string, max 4000), `client_message_id` (UUID). Повтор з тим самим ключем у тій самій кімнаті → **200** + `meta.duplicate: true`.

Доступ до кімнати: `access === 0` — усі авторизовані; `access > 0` — лише не-гості.

## Контракт вмісту (безпека UI)

- Поля **`post_message`**, **`post_user`**, **`topic`** у JSON — **не екрановані** сервером; це звичайні рядки UTF-8.
- **Vue / клієнт** зобов’язаний показувати їх як **текст** (наприклад `{{ }}` у Vue 2), а не як HTML, доки окремо не впроваджено санітизацію або явний дозволений підмножини HTML (як у legacy board.te.ua).
- Відповідь **POST** (у т. ч. **idempotent 200**) завжди містить **`meta.slash`**: `{ name: string|null, recognized: boolean }`. Для повтору того самого `client_message_id` повертається `recognized: false` (стан збереженого повідомлення не реконструюється з тіла повторного запиту).
