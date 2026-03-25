# T134 — Локальний перезапуск і smoke

**Дата:** 2026-03-25  
**Середовище:** macOS, репозиторій `backend/` як корінь PHP/Vite.

## Холодний старт (чекліст оператора)

1. Зупинити попередні процеси: `php artisan serve`, `npm run dev`, `php artisan reverb:start`, `php artisan queue:work` (якщо були).
2. Якщо після `php artisan config:cache` у Docker з’явився `bootstrap/cache/config.php` з шляхами на кшталт `/var/www/html`, **перед тестами на хості** виконати:
   ```bash
   cd backend && php artisan config:clear
   ```
   Інакше логи й storage вказуватимуть на неіснуючий каталог і PHPUnit падає з `UnexpectedValueException` на `storage/logs`.
3. За потреби: `composer install`, `npm ci`, `php artisan migrate`.
4. Підняти стек (приклад без Docker):
   - термінал 1: `cd backend && php artisan serve`
   - термінал 2: `cd backend && npm run dev`
   - для real-time: `php artisan reverb:start` і узгодити `VITE_REVERB_*` у `.env` (див. `docs/chat-v2/AGENT-ORCHESTRATION.md`).
   - за `QUEUE_CONNECTION=database`: `php artisan queue:work`.

## Автоматичні перевірки (артефакт PASS)

| Команда | Результат |
|--------|-----------|
| `cd backend && php artisan config:clear` | виконано перед suite |
| `cd backend && php artisan test` | **375 passed** (2026-03-25) |
| `cd backend && npm run build` | **PASS** (Vite 7) |

## Ручний smoke (без PII у скрінах/репо)

- Вітальня `/` → гість або логін → `/chat` → вибір кімнати → відправити повідомлення.
- Друга вкладка / інший браузер: повідомлення з’являється через Echo **або** очікуваний poll-fallback, якщо Reverb не запускали (зафіксувати в нотатці оператора).

## Підправка під T134 (регресія тестів)

- `chat:legacy-link-user-avatars`: fallback з недоступного env-шляху на `storage/app/legacy-avatars` застосовується лише коли **не** передано явний `--dir`; інакше команда коректно завершується з помилкою (`ChatLegacyLinkUserAvatarsTest`).
