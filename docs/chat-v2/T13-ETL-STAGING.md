# T13 — ETL legacy (org100h) у staging

**Мета:** безпечно підняти копію legacy board.te.ua у **окремій** MySQL-базі, перевірити цілісність, за потреби імпортувати **кімнати / користувачів / публічний чат** у **порожню** схему redpanda на staging.

**Не робити:** не класти паролі, дампи з PII й шляхи до секретів у git; не ганяти імпорт на production без окремого рішення та `--force`.

## 1. Підготовка дампу

1. Отримайте `org100h.sql` з довіреного джерела (файл описаний у `docs/board-te-ua/DATABASE-SCHEMA.md`; зміст може містити PII).
2. Створіть окрему БД на staging-сервері, наприклад `legacy_org100h`.
3. Імпорт (приклад; облікові дані лише в середовищі, не в репо):

```bash
mysql -h HOST -u USER -p legacy_org100h < /secure/path/org100h.sql
```

## 2. Змінні `.env` на staging (redpanda)

Основний додаток залишається на `DB_*`. Для читання legacy додайте (приклад):

```env
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=legacy_org100h
LEGACY_DB_USERNAME=...
LEGACY_DB_PASSWORD=...
```

Якщо `LEGACY_DB_DATABASE` порожній — команди `chat:legacy-*` одразу завершаться з підказкою.

## 3. Інспекція (звіт + сироти)

```bash
cd backend && php artisan chat:legacy-inspect
```

Очікування:

- Таблиці `users`, `chat`, `rooms`, `private`, `friends`, `images` — кількість рядків (якщо таблиці немає — `n/a`).
- **Сироти:** `chat` без рядка в `users` або без `rooms` — для коректного дампу зазвичай **0**.

Це і є **QA evidence** для T13 (кількості + перевірка сиріт).

## 4. Імпорт у порожню схему redpanda

1. Окрема схема для експерименту (наприклад `redpanda_staging_import`).
2. `php artisan migrate:fresh` **без** сидів, що створюють `users` / `rooms` / `chat`.
3. Переконайтеся, що `DB_*` вказують саме на цю порожню схему.
4. Оцінка обсягу без запису:

```bash
php artisan chat:legacy-import-staging --dry-run
```

5. Реальний імпорт (лише MySQL/MariaDB на основному з’єднанні; на production за замовчуванням заборонено без `--force`):

```bash
php artisan chat:legacy-import-staging
```

Підтвердження інтерактивне (`yes`). Після імпорту перевірте лічильники в БД або через UI.

### Обмеження імпорту (свідомо)

- **`chat.file`** виставляється в **0** (legacy часто зберігає не `images.id` redpanda).
- **Пароль:** переносяться лише вже **bcrypt** (`$2y$...`); значення на кшталт `new` або порожнє → `NULL` (вхід по паролю може вимагати скидання / Auth0).
- **Ролі:** грубе мапування legacy `user_rank` → `user_rank` redpanda (див. код `LegacyBoardImportService::mapLegacyRank`).
- **Приват, друзі, зображення** — **не** імпортуються в цій версії T13.
- Користувачі з `chat.user_id`, яких немає в legacy `users`, отримують stub `legacy_uid_{id}` (гість).
- **T113:** у цільову БД **не** імпортуються облікові записи з legacy `users`, у яких **немає жодного** рядка в **`chat`** (0 публічних постів); деталі та rsync аватарок — [docs/chat-v2/T113-LEGACY-AVATARS.md](T113-LEGACY-AVATARS.md).

## 5. Команди

| Команда | Призначення |
|--------|-------------|
| `php artisan chat:legacy-inspect` | Звіт по legacy + сироти |
| `php artisan chat:legacy-import-staging --dry-run` | Оцінка обсягу |
| `php artisan chat:legacy-import-staging` | Імпорт у порожні `rooms` / `users` / `chat` |
| `php artisan chat:legacy-sync-avatars` | Опційно (T113): rsync аватарок з legacy — `LEGACY_AVATAR_RSYNC_*` у `.env` |

Конфіг з’єднання: `config/database.php` → `legacy`. Параметри rsync: `config/legacy.php`.
