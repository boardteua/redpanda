# T76 — налаштування Auth0 (Dashboard + .env)

Ціль: **Universal Login** з соціальними провайдерами (**Google**, **Facebook**, **X**), а на бекенді — перевірка **access token** (JWKS), **без** переносу паролів у Auth0. **Гість** і **логін/пароль Laravel** не змінюються.

## Змінні середовища (Laravel `backend/.env`)

| Змінна | Призначення |
|--------|-------------|
| `AUTH0_ENABLED` | `true` щоб увімкнути перевірку JWT і блок соц-входу на вітальні |
| `AUTH0_DOMAIN` | Tenant host, напр. `dev-xxx.eu.auth0.com` (без `https://`) |
| `AUTH0_AUDIENCE` | **Identifier** вашого Auth0 API (Resource Server) — той самий рядок, що й `audience` у SPA |
| `AUTH0_SPA_CLIENT_ID` | **Client ID** застосунку типу **Single Page Application** (публічний; віддається в `GET /api/v1/landing` для ініціалізації SDK) |
| `AUTH0_JWKS_CACHE_TTL` | Опційно; секунди кешу JWKS (за замовчуванням `3600`) |

Опційно для суворішої перевірки токена на API: коли задано `AUTH0_SPA_CLIENT_ID`, бекенд перевіряє claim **`azp`** (має збігатися з Client ID SPA).

У репозиторії приклад ключів — у `backend/.env.example` (без секретів).

## Auth0 Dashboard — покроково

### 1. Tenant і регіон

Створіть або оберіть tenant; домен виду `{name}.eu.auth0.com` (або інший регіон) — його копіюєте в `AUTH0_DOMAIN`.

### 2. Application (SPA)

1. **Applications → Create Application → Single Page Application**.
2. **Allowed Callback URLs**:  
   - локально: `http://localhost:8000/auth/callback`, `http://127.0.0.1:8000/auth/callback`;  
   - якщо фронт на Vite: `http://localhost:5173/auth/callback` (усі реальні origin додайте явно).
3. **Allowed Logout URLs**: ті самі origins (напр. `http://localhost:8000`, `http://localhost:8000`).
4. **Allowed Web Origins**: ті самі origins без шляху.
5. Увімкніть **OIDC Conformant** (зазвичай увімкнено для нових додатків).
6. Скопіюйте **Client ID** → `AUTH0_SPA_CLIENT_ID`.

### 3. API (Resource Server)

1. **APIs → Create API**.
2. **Identifier** — довільний URL-подібний рядок (напр. `https://api.chat.example/`), він же **`AUTH0_AUDIENCE`** і той самий `audience` у коді SPA (приходить з `GET /api/v1/landing`).
3. **Signing Algorithm**: RS256 (типово).

### 4. Соціальні підключення (Connections)

У **Authentication → Social** увімкніть і налаштуйте:

- **Google** (`google-oauth2`)
- **Facebook** (`facebook`)
- **X / Twitter** (`twitter` — назва connection у Auth0; кнопка в UI підписана «X»)

У **Application → Connections** переконайтеся, що ці connections дозволені для вашого SPA.

Для **access token** з email (потрібно для зв’язку з імпортованими користувачами за email):

- У **APIs → ваш API → Settings**: за потреби додайте scope або використайте Auth0 **Actions** / **Rules**, щоб у access token потрапляли `email` / `email_verified` (узгодьте з політикою безпеки; мінімально можна обійтися без email — тоді створюється новий нік з `sub`).

### 5. Universal Login

**Branding → Universal Login**: мова, логотип, тощо — за бажанням. Редіректи вже задаються в Application.

### 6. Перевірка redirect flow

```text
Користувач → кнопка Google/Facebook/X на вітальні
  → Auth0 Hosted Login
  → редірект на https://{APP_ORIGIN}/auth/callback
  → Vue викликає handleRedirectCallback()
  → перехід у /chat
  → axios надсилає Authorization: Bearer {access_token}
  → Laravel: ResolveAuth0BearerUser + JWKS → provision / link User → auth:sanctum
```

Для **WebSocket** (Reverb) той самий Bearer передається в Echo (`bearerToken`), щоб `POST /broadcasting/auth` бачив користувача.

## Локальна розробка

1. Заповніть `.env` (див. таблицю вище), `AUTH0_ENABLED=true`.
2. `php artisan migrate` (колонка `users.auth0_subject`).
3. `npm run dev` або збірка через `npm run build` — переконайтеся, що **callback URL** у Auth0 збігається з фактичним origin SPA.
4. Перевірте: гість «Зайти анонімно» працює без Auth0; після соц-логіну — `/api/v1/auth/user` повертає зареєстрованого користувача.

## Колізії облікових записів

- Якщо email у токені збігається з існуючим **не-гостьовим** користувачем **без** `auth0_subject` — акаунт **прив’язується** (записується `auth0_subject`).
- Якщо email вже прив’язаний до **іншого** `sub` — API відповідає **409** (конфлікт; не лінкуємо автоматично).

## Безпека (коротко)

- **Client Secret** для SPA **не** використовується в браузері; лише PKCE + публічний Client ID.
- Не публікуйте в репозиторій реальні домени клієнта, якщо політика команди забороняє; для QA достатньо staging tenant.

Детальні офіційні документи: [Auth0 Universal Login](https://auth0.com/docs/authenticate/login/auth0-universal-login), [SPA SDK](https://auth0.com/docs/libraries/auth0-spa-js), [APIs](https://auth0.com/docs/get-started/apis).
