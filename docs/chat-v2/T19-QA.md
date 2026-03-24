# T19 — QA: безпека завантаження аватарок (і спільна перевірка для зображень чату)

| Поле | Значення |
|------|-----------|
| **Дата** | 2026-03-24 |
| **Вердикт** | **PASS** |

## Перевірені вектори

| Вектор | Очікування | Доказ |
|--------|------------|--------|
| Заявлений PNG, фактично пошкоджений/некоректний вміст після проходження `mimetypes` | **422**, `errors.image` | `UserAvatarApiTest::test_avatar_rejects_non_image_bytes_even_with_png_client_type` |
| Розміри зображення > **4096×4096** (аватар) | **422** | `UserAvatarApiTest::test_avatar_rejects_dimensions_above_avatar_limit` |
| Гість не завантажує аватар | **403** (без змін T18) | наявний тест `test_guest_upload_avatar_returns_403` |
| Підміна чужого `user_id` | Неможлива: лише `$request->user()` у `UserAvatarController` | код-рев’ю маршруту `POST /api/v1/me/avatar` |
| Викладення поза webroot | Як і раніше: диск **`chat_images`** → `storage/app/chat-images` | `config/filesystems.php` |
| Rate limit аватара окремо від вкладень чату | **`throttle:avatar-upload`** (10/хв звичайний, 20 VIP/mod, 3 guest-формально) | `routes/api.php`, `ChatThrottleRules`, `AppServiceProvider` |
| Зображення чату: вміст + ліміт **8192×8192** | Та сама інспекція в `ChatImageController` | регресія `ChatImageApiTest` |

## Автоматизовані тести

```bash
cd backend && composer test
```

Очікування: усі тести зелені (у т.ч. нові/оновлені для T19).

## Примітки

- **SVG** не в дозволених MIME (T18/T10) — залишається забороненим.
- Клієнтський `Content-Type` не використовується для поля `images.mime` після збереження — береться узгоджений з вмістом тип з інспектора.
