# T24 — QA (профіль зареєстрованого)

## Реалізація

- **БД:** міграція `2026_03_22_160000_add_profile_fields_to_users_table` — персональні поля, прапорці «приховано», `social_links` (JSON), `notification_sound_prefs` (JSON).
- **API:** `GET/PATCH /api/v1/me/profile`, `PATCH /api/v1/me/account`; гість — **403** (authorize у Form Request + `show`). Throttle: `me-profile` 60/хв, `me-account` 10/хв.
- **`GET /api/v1/auth/user`:** для не-гостя додаються `profile`, `social_links`, `notification_sound_prefs` (узгоджено з `UserResource`).
- **UI:** `UserProfileModal.vue` — вкладки Персональні / Акаунт / Соцмережі / Звуки; аватар через існуючий `POST /api/v1/me/avatar`; пункт «Профіль» і бургер відкривають модал замість заглушки; швидке завантаження аватара з сайдбару прибрано (один флоу — у модалі).

## Автоматичні перевірки

```text
cd backend && php artisan test tests/Feature/MeProfileApiTest.php
cd backend && npm run build
```

Повний набір: `php artisan test` — PASS (на момент закриття T24).

## Ручний сценарій (опційно)

1. Залогінитись зареєстрованим користувачем → відкрити «Профіль» (бургер або контекстне меню на своїй плашці).
2. Заповнити персональні поля, зберегти → оновити сторінку → дані з `auth/user` відновлюються.
3. Вкладка «Звуки» — змінити тумблери та гучність, зберегти → після перезавантаження значення ті самі.
4. Вкладка «Акаунт» — зміна e-mail з валідним поточним паролем; з невірним паролем — помилка валідації.

## Вердикт

**QA PASS** для T24 за результатами PHPUnit + `npm run build`.
