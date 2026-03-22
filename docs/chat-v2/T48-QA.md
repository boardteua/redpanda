# T48 — QA (статус користувача: онлайн / відійшов / неактивний)

## Рішення

- **Три стани:** `online` (вкладка видима й idle &lt; 180 с), `away` (вкладка у фоні **або** idle ≥ 180 с), `inactive` (idle ≥ 600 с). Пороги на бекенді в `config/chat.php` (`presence_*_idle_seconds`); клієнт використовує ті самі дефолти у `ChatRoom.vue`.
- **Синхронізація:** клієнт шле `POST /api/v1/rooms/{room}/presence-status` при зміні статусу та як heartbeat (~45 с); сервер зберігає в **кеш** (TTL `presence_status_ttl_seconds`) і транслює **`.PresenceStatusUpdated`** на той самий presence-канал кімнати. Початкове наповнення для списку пірів — `GET .../presence-statuses?user_ids=...` після `here()` / debounce; у режимі poll деградації — той самий GET разом з опитуванням повідомлень.
- **UI:** крапка-індикатор (зелений / бурштиновий / сірий) з `title` та `aria-label` українською; для `away` та `inactive` — `grayscale` + легка зміна opacity на рядку (`.rp-presence-row--*` у `ChatRoomSidebar.vue`).

## Автоматичні перевірки

```bash
cd backend && php artisan test --filter=RoomPresenceStatusTest
cd backend && php artisan test
cd backend && npm run build
```

Очікування: усі тести PASS; збірка без помилок.

## Ручний сценарій (опційно)

1. Два браузери, одна публічна кімната, Reverb увімкнено.
2. Користувач A: залишити вкладку у фоні — у B має з’явитись «відійшов» (після оновлення статусу / WS).
3. Довгий idle без вводу — «неактивний».
4. Повернення у вкладку й рух миші — знову «онлайн».

## Вердикт

- **PASS (автоматичні):** `php artisan test` — 150 тестів OK; `npm run build` — OK (дата в коміті).
