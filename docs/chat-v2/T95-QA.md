# T95 — QA (production admin `org100h`, міграції в деплої)

## Що зроблено в репозиторії

| Артефакт | Опис |
|----------|------|
| `backend/database/migrations/2026_03_24_120000_t95_promote_org100h_to_chat_admin.php` | Разова data-migration: `user_name = org100h`, не гість → `user_rank = 2` (адмін чату); ідемпотентна |
| `docker/deploy.sh` | Уже викликав `php artisan migrate --force` у контейнері `php` після `composer install`; додано **`echo "[deploy] php artisan migrate --force"`** для явного рядка в логах деплою |
| `.github/workflows/ci.yml` | Job **`deploy`** (push у `main`) виконує на сервері **`./docker/deploy.sh`** — тобто міграції йдуть тим самим шляхом, що й локальний VPS-деплой |

## Локальні перевірки (розробник / CI)

```bash
cd backend && composer test
cd backend && php artisan migrate --no-interaction --force
```

## Доказ PASS (оператор після релізу на prod)

1. У логу SSH-деплою / GitHub Actions — рядок **`[deploy] php artisan migrate --force`** і далі успішний вивід Artisan migrate (без секретів у скріншоті).
2. `php artisan migrate:status` на prod — міграція **`2026_03_24_120000_t95_promote_org100h_to_chat_admin`** у статусі **Ran**.
3. Користувач **`org100h`** у staff/UI або в БД має **`user_rank = 2`** і `guest = 0`; вхід дає адмін-права в чаті.

**Вердикт:** PASS після зеленого `composer test` і підтвердження пунктів 1–3 на реальному production (заповнює оператор).
