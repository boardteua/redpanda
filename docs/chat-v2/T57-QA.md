# T57 — Менеджер користувачів (staff): каталог, фільтри, bulk, CRUD

## Статус: PASS

## Бекенд

- `GET /api/v1/mod/users`: каталог (`browse=1`), пошук `q`, фільтри `guest`, `user_rank`, `vip`, `muted`, `kicked`, `disabled`, сортування `sort` / `direction`, `per_page` до 100. Без `q`, без `browse` і без фільтрів — **422**.
- `GET /api/v1/mod/users/{user}`: один рядок (payload як у списку).
- `POST /api/v1/mod/users`: створення зареєстрованого; без пароля — `meta.generated_password` (один раз у відповіді).
- `POST /api/v1/mod/users/bulk`: до 50 id, `can:chat-admin`, транзакція; дії `set_vip`, `clear_vip`, `set_rank`, `mute`/`kick` (+ `minutes`), `clear_*`, `disable_account`/`enable_account`.
- `PATCH /api/v1/mod/users/{user}`: додано `account_disabled` (soft-disable через `account_disabled_at`).
- Міграція `account_disabled_at`; логін відхиляє вимкнений запис; middleware `RejectDisabledAccount` на всіх `auth:sanctum` API-роутах скидає сесію при вимкненому акаунті.

## Тести

- `php artisan test tests/Feature/StaffUserManagementApiTest.php tests/Feature/AuthApiTest.php` — сценарії каталогу, bulk, create, PATCH disable, логін/middleware для disabled.
- Повний `php artisan test` — **198 passed**.

## Фронт

- `StaffUsersView.vue`: стартовий каталог, фільтри, чекбокси та масові дії (модал `RpModal`), створення користувача, колонка статусу (вимкн./мут/kick), окремий прапорець вимкнення облікового запису в блоці редагування.

## Збірка

- `npm run build` — PASS.

## OpenAPI

- Оновлено `docs/chat-v2/openapi.yaml` (описи ендпоінтів, схеми bulk/create, поля рядка користувача).
