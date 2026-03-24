# T111 — Legacy `user_password`: формати та шляхи міграції

**Задача:** [project-tasks/chat-v2-tasklist.md](../../project-tasks/chat-v2-tasklist.md) — T111.  
**Трасування:** [docs/board-te-ua/DATABASE-SCHEMA.md](../board-te-ua/DATABASE-SCHEMA.md) §4.1 (`user_password`, у документації зазначено bcrypt `$2y$10$...`); **T76** — пароль залишається в Laravel, не в Auth0 Database.

## 1. Поточна поведінка redpanda (імпорт T13 / T113)

У **`LegacyBoardImportService::legacyPasswordForStorage`** у цільову БД потрапляє пароль **лише** якщо:

- користувач **не** гість;
- після `trim` рядок **не** порожній і **не** рівний `'new'` (без урахування регістру);
- рядок **починається з `$2y$`** (bcrypt у формі, яку історично писав PHP).

Усі інші значення стають **`NULL`** у `users.password` після імпорту — вхід «старим паролем» неможливий; користувач може скористатися **скиданням пароля** (**T94**), якщо є валідний email у профілі.

Логін SPA: **`AuthController::login`** — **`Hash::check($plain, $user->password)`** (стандартний bcrypt-hasher Laravel, фактично **`password_verify`** у PHP).

Імпорт робить **`DB::table('users')->insert(...)`**, тобто обходить cast **`password` => `hashed`** у моделі **`User`** — у БД зберігається **вже готовий** хеш з legacy, без повторного хешування.

## 2. Збір статистики на довіреній копії legacy (без витоку хешів у git)

Виконувати на **копії** БД (staging / локальний імпорт дампу), не на проді в режимі експерименту. **Не** вставляти в репозиторій, тікети чи логи **реальні** значення `user_password` — лише **агреговані лічильники**.

Нижче — шаблон для **MySQL/MariaDB** (таблиця **`users`**, колонка **`user_password`**, гості **`guest`**; за потреби підлаштуйте імена під фактичну схему дампу).

### 2.1. Розподіл по «відрах» (взаємовиключні категорії)

Порядок **`CASE`** важливий: спочатку специфічні префікси, потім загальні евристики.

```sql
SELECT bucket, COUNT(*) AS c
FROM (
  SELECT
    CASE
      WHEN user_password IS NULL OR TRIM(user_password) = '' THEN 'empty'
      WHEN LOWER(TRIM(user_password)) = 'new' THEN 'literal_new'
      WHEN TRIM(user_password) LIKE '$2y$%' THEN 'bcrypt_2y'
      WHEN TRIM(user_password) LIKE '$2a$%' THEN 'bcrypt_2a'
      WHEN TRIM(user_password) LIKE '$2b$%' THEN 'bcrypt_2b'
      WHEN TRIM(user_password) LIKE '$2x$%' THEN 'bcrypt_2x'
      WHEN TRIM(user_password) LIKE '$P$%' OR TRIM(user_password) LIKE '$H$%' THEN 'phpass'
      WHEN TRIM(user_password) REGEXP '^[a-fA-F0-9]{32}$' THEN 'md5_hex_32'
      ELSE 'other'
    END AS bucket
  FROM users
  WHERE IFNULL(guest, 0) = 0
) t
GROUP BY bucket
ORDER BY c DESC;
```

**Інтерпретація (орієнтовно):**

| Bucket | Зміст |
|--------|--------|
| `bcrypt_*` | Сімейство bcrypt; **`password_verify`** у PHP зазвичай приймає **`$2y$` / `$2a$` / `$2b$`** (і часто `$2x$` як варіант движка). |
| `phpass` | Типові WordPress/phpass portable hashes. |
| `md5_hex_32` | Підозра на «голий» MD5; **не** плутати з випадковим hex рядком іншого походження — уточнюйте по вибірці коду legacy. |
| `other` | Потребує ручного розбору (plain text, кастомний salt+hash, застарілі схеми). |

### 2.2. Довжина та префікс (додаткова діагностика)

Корисно окремо порахувати, скільки рядків мають довжину **менше за 50** символів (можлива plaintext / короткий токен), **не** потрапивши в відомі префікси:

```sql
SELECT
  COUNT(*) AS total_registered,
  SUM(CASE WHEN LENGTH(TRIM(IFNULL(user_password, ''))) < 50
            AND TRIM(IFNULL(user_password, '')) NOT LIKE '$%'
       THEN 1 ELSE 0 END) AS short_non_dollar_prefix
FROM users
WHERE IFNULL(guest, 0) = 0;
```

Результати фіксуйте **внутрішньо** (оператор / PM), не в git.

## 3. Варіанти рішення для production

### A. Залишити як є (лише `$2y$` при імпорті)

- **Коли доречно:** у дампі **майже всі** паролі вже `$2y$`, або готові змиритися з часткою акаунтів без пароля до reset.
- **Плюси:** мінімум коду, немає кастомної криптографії.
- **Мінуси:** користувачі з валідним **`$2a$` / `$2b$`** (так само bcrypt) **втрачають** пароль при імпорті через поточну перевірку префікса — це **штучне** обмеження, а не обмеження PHP/Laravel.

### B. Розширити імпорт на всі префікси bcrypt (`$2a$`, `$2b$`, `$2y$`, за потреби `$2x$`)

- **Коли доречно:** після SQL з п.2 видно ненульові **`bcrypt_2a` / `bcrypt_2b`**.
- **Реалізація:** ослабити умову в **`legacyPasswordForStorage`** до перевірки «схоже на bcrypt» (наприклад префікси `$2a$`, `$2b$`, `$2y$`, `$2x$` + мінімальна довжина ~60) **або** `password_get_info($hash)['algo'] === PASSWORD_BCRYPT` після легкої валідації рядка.
- **Логін:** без змін — **`Hash::check`** уже використовує **`password_verify`**.
- **Re-hash після входу (рекомендовано Laravel-патерн):** після успішного **`Hash::check`**, якщо **`Hash::needsRehash($user->password)`** — оновити пароль через Eloquent (`$user->password = $plain` + `save()`), щоб cast **`hashed`** записав новий **`$2y$`** з актуальним cost.

### C. Кастомна перевірка для MD5 / phpass / іншого + перехід на bcrypt

- **Коли доречно:** значні **`md5_hex_32`**, **`phpass`**, **`other`** з відомим алгоритмом у коді legacy board.te.ua.
- **Підхід:** окремий клас (напр. **`LegacyPasswordVerifier`**) з **одним** місцем перевірки; у **`AuthController::login`** спочатку **`Hash::check`**, якщо false — fallback за типом збереженого рядка; при успіху — **одразу** перезапис **`users.password`** у bcrypt (**rehash**).
- **Ризики:** помилка в відтворенні salt/конкатенації legacy = зламаний вхід або дірка; обов’язковий **code review** + тести з **синтетичними** хешами (не з prod).

### D. Примусове скидання пароля для частки акаунтів

- **Коли доречно:** формат **небезпечний** (plain text у БД), **невідновний**, або політика безпеки вимагає нових паролів.
- **Механіка:** імпорт з **`password = NULL`**, комунікація користувачам + **T94**; за відсутності email — ручний сценарій підтримки.

### E. Явна відмова підтримувати застарілий формат

- Документувати в runbook: частка акаунтів **не** мігрує пароль; лише reset або новий обліковий запис.

## 4. Висновок для production (за замовчуванням, до уточнення по SQL)

1. **Обов’язково** виконати запити з **§2** на **довіреній копії** legacy і зафіксувати таблицю bucket → `c` **поза git**.
2. **До появи статистики:** залишаємо поточний імпорт (**лише `$2y$`**) — це узгоджено з **T13** і не вимагає змін коду.
3. **Після статистики:**
   - якщо є **`bcrypt_2a` / `bcrypt_2b` / `bcrypt_2x`** — **рекомендовано** застосувати варіант **B** (розширення префіксів + опційно **needsRehash** після логіну);
   - якщо є **phpass / MD5 / інше** — варіант **C** або **D** за результатами аудиту legacy-коду та рішенням безпеки;
   - **plain text** у **`other`** або короткі рядки без хеш-префікса — пріоритетно **D** або **E**, не намагатися «підтримувати» plaintext у новому додатку.

4. **Auth0:** згідно з **T76**, пароль лишається в Laravel для цього флоу; узгодження з єдиним входом через Auth0 — окреме рішення продукту.

## 5. Посилання в коді

- Імпорт: `backend/app/Services/LegacyBoardImport/LegacyBoardImportService.php` — **`legacyPasswordForStorage`**.
- Логін: `backend/app/Http/Controllers/Api/V1/AuthController.php` — **`login`**.
- Скидання: `backend/app/Http/Controllers/Api/V1/PasswordResetController.php` — **T94**.

QA: [docs/chat-v2/T111-QA.md](T111-QA.md).
