# T86 — QA evidence

**Вердикт:** PASS

**Задача:** ліміт розміру вкладень у повідомленнях чату; серверна валідація; контракт у `GET/PATCH /api/v1/chat/settings`; UI в «Налаштування чату»; клієнтська перевірка в композері за `max_chat_image_upload_bytes`.

**Доказ:**

- `cd backend && php artisan test` — **294 passed** (повний набір після змін).
- `cd backend && npm run build` — **успішно** (Vite 7).

**Примітки:** фактична межа завантаження = `min(max_attachment_bytes, PHP upload_max_filesize)`; у відповіді API це поле `max_chat_image_upload_bytes`. Аватар (`POST /api/v1/me/avatar`) використовує той самий ефективний ліміт для узгодження з T10/T18.
