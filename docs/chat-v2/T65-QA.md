# T65 — QA: звуки newpost / pmsound + favicon для непрочитаних приватів

## Вердикт

**PASS** (автоматична перевірка збірки; ручні сценарії — для оператора).

## Доказ

- `npm run build` у каталозі `backend/` — **успішно** (Vite 7).
- PHPUnit: `php artisan test --filter=PrivateMessageApiTest` — регресії по `total_private_unread` не очікуються (зміни лише у фронті).

## Реалізація (коротко)

- **`/sounds/newpost.mp3`** — лише для **нових** повідомлень у **поточній** кімнаті, **не від себе**, якщо увімкнено `notification_sound_prefs.public_messages`; у **фоновій** вкладці звук **не** відтворюється (крім майбутнього прапорця **`chatSettings.sound_on_every_post`** для паритету з **T75**, коли поле з’явиться в API).
- **`/sounds/pmsound.mp3`** — при вхідному **`PrivateMessagePosted`** (не від себе), якщо увімкнено `notification_sound_prefs.private`; у фоновій вкладці **не** глушиться (узгоджено з формулюванням задачі).
- **Інлайн-приват** у стрічці (`type === 'inline_private'`) — **без** newpost (отримувач уже чує pmsound), щоб уникнути подвійного сигналу.
- **Favicon** — пакет **[favicon-badge-notify](https://github.com/jsdeveloperr/favicon-badge-notify)**; базова іконка **`/board-te-ua-favicon.ico`** (файл з legacy [board.te.ua](https://www.board.te.ua/) — у HTML там `<link rel="shortcut icon" href="favicon.ico" />`). Бейдж за **`totalPrivateUnread`** (T56); при **0** — знову базова іконка.
- **Autoplay:** відтворення після **першої** `pointerdown` / `keydown` на вікні (обхід політики браузера).

## Ручний чекліст (оператор)

1. Два клієнти, **Reverb + queue:work**, активна кімната, вкладка **на передньому плані** → чується **newpost** при чужому повідомленні (якщо звук увімкнено в профілі).
2. Та сама кімната, вкладка **у фоні** → **newpost** немає.
3. Вхідний приват → **pmsound** + оновлення **favicon** (бейдж); після відкриття треду лічильник і favicon зменшуються.
4. Вимкнути «Звичайні повідомлення» у профілі → newpost не грає; вимкнути приват → pmsound не грає.

## Відомі обмеження

- **Safari** може по-різному кешувати `favicon`; за потреби перевірити 16×16/32×32.
- **`sound_on_every_post`** (legacy board.te.ua) — очікує поле в **`GET /api/v1/chat/settings`** після **T75**; до цього завжди `false`.
