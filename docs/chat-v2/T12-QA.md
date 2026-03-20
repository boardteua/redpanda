# T12 — Модерація MVP (QA)

**Задача:** бан IP (таблиця `banned_ips`), слова-фільтр (`filter_words`), mute/kick на `users` (`mute_until`, `kick_until`, `user_rank`); мінімальний API для модераторів.

## Автоматичні перевірки

```bash
cd backend && php artisan test --filter=ModerationApiTest
cd backend && php artisan test
cd backend && npm run build
```

## Поведінка

| Механізм | Де застосовується |
|----------|-------------------|
| **Бан IP** | Middleware на весь префікс `api/v1/*` — **403** JSON, якщо `request->ip()` є в `banned_ips` (кеш ~120 с). |
| **Фільтр слів** | Після slash-pipeline у **публічному** чаті та в **приватних** повідомленнях — підрядки (без урахування регістру) замінюються на `*` тієї ж довжини. Список слів кешується; скидання при зміні через API. |
| **Mute / kick** | Блок **лише відправки**: кімнатні повідомлення, приват, завантаження зображень. Читання API не обмежується. Повідомлення: мут — «Ви в муті…», kick — «Вас тимчасово відключено…». |

## Ранги (`users.user_rank`)

| Значення | Константа | Хто |
|----------|-----------|-----|
| 0 | `RANK_USER` | Звичайний користувач |
| 1 | `RANK_MODERATOR` | Модератор (`Gate::define('moderate', …)`) |
| 2 | `RANK_ADMIN` | Адмін (той самий `can:moderate`; пріоритет вище за модератора в ієрархії дій) |

Перший модератор у чистій БД: через tinker / SQL, наприклад  
`User::where('user_name', '…')->first()?->forceFill(['user_rank' => User::RANK_MODERATOR])->save();`

Дія щодо користувача з **таким самим або вищим** `user_rank` — **403**.

## API модерації (`auth:sanctum`, `can:moderate`, throttle `mod-actions`)

Усі під префіксом **`/api/v1/mod/`** (деталі та схеми — [openapi.yaml](openapi.yaml)).

- `GET|POST /mod/banned-ips`, `DELETE /mod/banned-ips/{id}`
- `GET|POST /mod/filter-words`, `DELETE /mod/filter-words/{id}`
- `POST /mod/users/{user}/mute` — тіло `{ "minutes": N }`; **зняти мут:** `{}` (поле відсутнє) або `{ "minutes": 0 }`
- `POST /mod/users/{user}/kick` — аналогічно для `kick_until`

Приклад бану IP (після входу модератора, cookie + CSRF як для інших POST):

```bash
curl -sS -b cookies.txt -H "Content-Type: application/json" -H "X-XSRF-TOKEN: …" \
  -d '{"ip":"198.51.100.10"}' https://example.com/api/v1/mod/banned-ips
```

## Вердикт

| Поле | Значення |
|------|----------|
| **Статус** | PASS (за наявності тестів вище) |
| **Дата** | 2026-03-20 |
