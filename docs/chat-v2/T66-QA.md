# T66 — QA (slash-команди: інфраструктура)

**Вердикт:** PASS

## Автоматичні перевірки

- `php artisan test` — усі тести зелені (у т.ч. `ChatApiTest` для escape `//`, `client_only`, кеш ідемпотентності, `/noop`; `SlashCommandLineParserTest`).
- `npm run build` — без помилок.

## Контракт API (коротко)

- `//…` → публічне повідомлення з текстом після зняття одного `/`; `meta.slash.escaped: true`, `result: public_message`.
- Невідома `/команда` → **200**, `data: null`, `meta.slash.result: client_only`, `meta.client_only.style: terminal`, без рядка в `chat`.
- Повтор з тим самим `client_message_id` після `client_only` повертає ту саму закешовану `meta` (без запису в БД).
- Окремий ліміт slash: 25 спроб / 60 с на користувача → **429** з повідомленням українською.

## Фронтенд

- Відповідь `client_only` з непорожніми `lines` додає локальний рядок типу `slash_client_only` (моноширинний вивід з префіксом `>`).
- Композер: підказка візуально (`rp-chat-composer-input--slash`), коли рядок виглядає як команда (один `/`, не `//`).

## Примітки для T67+

- Реєстр обробників у `SlashCommandRegistry`; нові команди реєструються в `SlashCommandPipeline::buildDefaultRegistry()`.
