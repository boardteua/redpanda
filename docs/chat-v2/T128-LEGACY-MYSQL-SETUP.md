# T128 — MySQL: окрема база `legacy_*` для ETL (org100h)

**Контекст:** redpanda читає legacy лише через з’єднання **`LEGACY_DB_*`**; дамп **org100h** не повинен потрапляти в `DB_DATABASE` додатку.

**Виконувати під обліковим записом адміністратора MySQL** (root або користувач з правом `CREATE DATABASE`). Паролі та реальні імена користувачів **не** комітити — лише на сервері.

---

## 1. Створити базу

```sql
CREATE DATABASE IF NOT EXISTS legacy_org100h
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

Ім’я можна змінити; тоді те саме значення виставте в **`LEGACY_DB_DATABASE`**.

---

## 2. Імпорт дампу

На сервері (шлях до `.sql` — ваш контрольований каталог):

```bash
mysql -h 127.0.0.1 -u root -p legacy_org100h < /secure/path/org100h.sql
```

Перевірка:

```bash
mysql -h 127.0.0.1 -u root -p -e "USE legacy_org100h; SHOW TABLES LIKE 'users'; SHOW TABLES LIKE 'chat';"
```

---

## 3. Окремий користувач лише для читання legacy (рекомендовано)

Якщо ви вже створили користувача — достатньо видати права на **цю** базу:

```sql
-- Замініть ім’я користувача/хост під вашу політику:
CREATE USER IF NOT EXISTS 'redpanda_legacy_ro'@'localhost' IDENTIFIED BY 'СИЛЬНИЙ_ПАРОЛЬ_ЛИШЕ_НА_СЕРВЕРІ';

GRANT SELECT ON `legacy_org100h`.* TO 'redpanda_legacy_ro'@'localhost';
FLUSH PRIVILEGES;
```

Якщо додаток і MySQL на різних хостах — замініть `'localhost'` на відповідний хост або `'%'` (свідомо, з обмеженням firewall).

---

## 4. `.env` redpanda (лише на сервері)

```env
LEGACY_DB_HOST=127.0.0.1
LEGACY_DB_PORT=3306
LEGACY_DB_DATABASE=legacy_org100h
LEGACY_DB_USERNAME=redpanda_legacy_ro
LEGACY_DB_PASSWORD=...
```

Перевірка з каталогу `backend/`:

```bash
php artisan chat:legacy-inspect
```

---

## Пов’язано

- [T128-LEGACY-PROD-IMPORT-RUNBOOK.md](T128-LEGACY-PROD-IMPORT-RUNBOOK.md)
- [T13-ETL-STAGING.md](T13-ETL-STAGING.md)
