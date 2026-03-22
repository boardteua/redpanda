# T46 — QA: універсальний рендер тіла повідомлення

**Вердикт:** PASS

## Автоматичні перевірки

- `npm run test:msg-parse` (Node built-in test runner) — PASS: літерали з `<script>`, http-посилання, `.png`, YouTube/Spotify, суфікс після URL.
- `npm run build` (Vite 7) — PASS.
- `php artisan test` — PASS (контракт API без змін; `post_message` лишається plain text).

## Безпека (узгоджено з T35)

- Текст сегментів рендериться через інтерполяцію Vue (`{{ }}`), **без** `v-html` для вмісту користувача.
- `href` / `src` iframe — лише після `new URL()`, тільки `http`/`https`, без облікових даних у URL.
- Ембеди: `iframe` з `src`, побудованим з allowlist (YouTube / Spotify / `embed.music.apple.com`).

## Ручний чекліст (оператор)

1. У публічному чаті: повідомлення з `https://…` — клікабельне посилання, `rel="noopener noreferrer"`.
2. URL на `.png` / `.jpg` — прев’ю з `loading="lazy"`, обмежена висота.
3. `https://www.youtube.com/watch?v=…` або `youtu.be/…` — вбудований плеєр у стрічці.
4. `https://open.spotify.com/track/…` — компактний embed.
5. Текст на кшталт `<script>alert(1)</script>` без `https://` — лишається текстом, без виконання скриптів.
6. Архів: ті самі URL; ембеди показані як посилання «Відкрити»; картинки за URL — компактні.
7. Приват: та сама логіка, що в стрічці (variant `private`).

## Артефакти

- Рішення без нових npm-залежностей: `docs/chat-v2/T46-SPIKE.md`.
- Парсер: `backend/resources/js/utils/chatMessageBodyParse.js`.
- Компонент: `backend/resources/js/components/chat/ChatMessageBody.vue`.
