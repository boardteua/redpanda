# T52 — Інтерфейс менеджменту та модерації користувачів (staff)

## Статус: PASS (оновлено: панель лише для адміна)

## Матриця прав (бекенд)

- `GET /api/v1/mod/users`, `PATCH /api/v1/mod/users/{user}`, `PATCH /api/v1/mod/users/{user}/profile` — **`can:chat-admin`**; throttle `mod-user-read` / `mod-actions`. Пошук: підрядок у `user_name` (`instr(lower(...))`), збіг за `id` якщо `q` лише цифри; пошук за `email`, якщо в `q` є `@`. У відповіді поле `email` для не-гостей.
- **Модератор** до цих ендпоінтів **не** допускається (**403**). **Mute/kick** з контекстного меню — окремо: `POST …/mute|kick` під **`can:moderate`** (**T12**).
- `PATCH …/{user}` — `vip` та `user_rank`; ціль має нижчий ранг за актора (`User::canReceiveStaffManagementFrom`); не на себе.
- `PATCH …/profile` — не для гостя; повний набір полів як у `MeProfileController` (соцмережі, звуки, профіль).
- Аудит: `staff.user.roles_updated`, `staff.user.profile_updated` у логах.

## Тести

- `php artisan test tests/Feature/StaffUserManagementApiTest.php` — див. актуальний вивід (модератор: 403 на весь staff user API).
- Повний `php artisan test` — перевіряти при змінах.

## Фронт

- Маршрут `/chat/staff-users` (`StaffUsersView.vue`): доступ лише якщо `chat_role === 'admin'`; інакше редірект у чат.
- Пункт меню «я» → «Користувачі (адмін)» — лише для **адміна** (`userBadgeMenuItems.js`).

## Документація

- `docs/chat-v2/openapi.yaml` — `modStaffUserSearch`, `modStaffUserPatchRoles`, `modStaffUserPatchProfile` (опис `can:chat-admin`).

## Опційно для оператора

- Перевірка 403 модератора на `GET /api/v1/mod/users`; mute під модератором лишається на `POST …/mute`.
