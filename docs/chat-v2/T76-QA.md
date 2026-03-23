# T76 — QA (Auth0 + соціальний вхід)

## Автоматично

- `cd backend && php artisan test --filter=LandingApiTest` — у відповіді `GET /api/v1/landing` є `data.auth0` з полями `enabled`, `domain`, `client_id`, `audience`; у типовому `.env` без Auth0 `enabled === false`.
- `php artisan test` — повний набір без регресій **T02** (Sanctum cookie) та broadcasting.
- `npm run build` у `backend/` — збірка з `@auth0/auth0-spa-js`.

## Вручну (staging / локально з реальним tenant)

1. Увімкнути `AUTH0_ENABLED=true`, заповнити `AUTH0_*` згідно з [T76-auth0-setup.md](./T76-auth0-setup.md).
2. Callback у Auth0: `{origin}/auth/callback` збігається з фактичним URL SPA.
3. **Google / Facebook / X**: успішний вхід → редірект на чат → повідомлення відправляються, presence / WS працюють (Reverb + Bearer на `/broadcasting/auth`).
4. **Регресія гостя**: без Auth0 увійти анонімно → чат доступний, без JWT.
5. **Логін/пароль**: без змін; після виходу з соц-сесії — `logout` очищає Laravel-сесію та викликає Auth0 `logout` (коли був соц-вхід).

## Негативні кейси

- Запит до захищеного API з невалідним або простроченим Bearer → **401**.
- Свідомий конфлікт email / інший `sub` → **409** при першому зверненні з валідним токеном (див. provisioner).
