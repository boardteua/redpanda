# T21 — QA (RBAC чату)

**Вердикт:** PASS

## Рішення (коротко)

- Роль для UI: `App\Chat\ChatRole` + `User::resolveChatRole()` (гість → staff за `user_rank` → VIP-прапор).
- Кімнати: `rooms.access` — `0` / `1` / `≥2` (VIP); `RoomPolicy::interact` і список кімнат узгоджені.
- Модерація: `can:moderate` — mute/kick; `can:chat-admin` — бан IP і фільтр слів (`user_rank ≥ 2`).
- Ліміти: `chat-post` і `image-upload` у `AppServiceProvider` залежать від гостя / VIP / staff.
- Гості: без завантаження зображень у чат і без `image_id` у повідомленні; коротший ліміт довжини тексту.
- API: `UserResource` і `GET .../users/lookup` — `chat_role`, `badge_color`; presence — ті самі поля; `post_color` у повідомленнях — `guest|user|vip|mod|admin`.
- Ліміти `chat-post` / `image-upload`: `App\Support\ChatThrottleRules` + unit-тести `tests/Unit/ChatThrottleRulesTest.php` (пороги не дублюються в `AppServiceProvider`).

## Доказ

```text
cd backend && php artisan test
# Tests: 77 passed
```

```text
cd backend && npm run build
# (очікується успішна збірка Vite)
```

Ручний сценарій (опційно): користувач з `vip=1` бачить VIP-кімнату в списку; звичайний — ні; модератор без VIP підписується на presence VIP-кімнати.

## Примітки

- Призначення `user_rank` / `vip` у проді — через адмін-інструменти або сидери (у репозиторії без PII).
