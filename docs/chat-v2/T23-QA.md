# T23 — QA (Staff: IP-checker / мережевий огляд у «Інформації»)

**Вердикт:** PASS

**Доказ:**

- `cd backend && php artisan test --filter=ModUserNetworkInsightApiTest` — усі тести зелені (403 для не-персоналу; 200 + payload з `sessions`; порожній стан без рядків у `sessions`).
- `cd backend && npm run build` — збірка без помилок.

**Примітки:** дані беруться з таблиці `sessions` за `user_id` (очікується `SESSION_DRIVER=database`). Перегляд логуються як `staff.network_insight.viewed` (actor + subject, без IP у логах).
