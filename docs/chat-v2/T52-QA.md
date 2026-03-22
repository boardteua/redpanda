# T52 — Інтерфейс менеджменту та модерації користувачів (staff)

## Статус: PASS

## Матриця прав (бекенд)

- `GET /api/v1/mod/users` — `can:moderate`, throttle `mod-user-read`. Пошук: підрядок у `user_name` (`instr(lower(...))`), збіг за `id` якщо `q` лише цифри; пошук за `email` — **лише для адміна** і лише якщо в `q` є `@`. У відповіді поле `email` — лише для адміна та не-гостей.
- `PATCH /api/v1/mod/users/{user}` — `vip` (мод+адмін) та `user_rank` (**лише адмін**); ціль має нижчий `user_rank` за актора; не на себе (`User::canReceiveStaffManagementFrom`).
- `PATCH /api/v1/mod/users/{user}/profile` — не для гостя; **модератор:** лише `profile.occupation`, `profile.about`; **адмін:** розширений профіль, `social_links`, `notification_sound_prefs` (як у `MeProfileController`).
- Аудит: `staff.user.roles_updated`, `staff.user.profile_updated` у логах.

## Тести

- `php artisan test tests/Feature/StaffUserManagementApiTest.php` — 11 passed.
- Повний `php artisan test` — 169 passed (на момент закриття T52).

## Фронт

- Маршрут `/chat/staff-users` (`StaffUsersView.vue`): пошук, таблиця, панель редагування (VIP, ранг для адміна, профіль за матрицею).
- Пункт меню «я» → «Користувачі (персонал)» для модератора та адміна (`userBadgeMenuItems.js`).

## Документація

- `docs/chat-v2/openapi.yaml` v0.16.3 — `modStaffUserSearch`, `modStaffUserPatchRoles`, `modStaffUserPatchProfile`.

## Опційно для оператора

- Скрін таблиці + зміна VIP під модератором; зміна рангу під адміном; перевірка 403 для звичайного користувача на `GET /api/v1/mod/users`.
