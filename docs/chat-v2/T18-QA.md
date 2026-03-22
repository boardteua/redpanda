# T18 — Завантаження аватарки для зареєстрованих (QA)

**Задача:** `POST /api/v1/me/avatar` (multipart `image`), поле `users.avatar_image_id` → `images`; гість — **403**; `UserResource.avatar_url`; нові повідомлення в чаті отримують `avatar` з URL файлу; UI «Змінити аватарку» у вкладці «Люди» лише для `guest === false`.

## Автоматичні докази

```bash
cd backend && php artisan test --filter=UserAvatarApiTest
cd backend && php artisan test
cd backend && npm run build
```

- `UserAvatarApiTest`: успішний upload зареєстрованого, 403 для гостя, заміна аватара (видалення старого рядка `images`, якщо не використовується як вкладення в `chat.file`), збереження старого рядка якщо є посилання з `chat.file`, інший користувач може `GET /api/v1/images/{id}/file` для чужої аватарки профілю (політика `ImagePolicy`).
- Повний PHPUnit і збірка Vite — без регресій.

## Ручний чекліст (браузер)

1. Зареєстрований користувач: `/chat` → панель → «Люди» → «Змінити аватарку» → обрати JPEG/PNG → аватар у рядку «(ви)» і в стрічці після нового повідомлення.
2. Гість: кнопки завантаження аватарки немає; `POST /api/v1/me/avatar` повертає 403 (див. тести).
