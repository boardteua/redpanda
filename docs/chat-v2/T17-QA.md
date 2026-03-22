# T17 — Аватарки: зображення з API + fallback на ініціали (QA)

**Задача:** спільний компонент `UserAvatar` (логіка ініціалів на кшталт vue-avatar: два слова → дві літери, одне слово → дві перші графеми); при непорожньому `avatar` / `avatar_url` — `<img>` з fallback при помилці завантаження; підключено в стрічці кімнати, сайдбарі (ви, друзі, приват, ігнор), приватній панелі та архіві.

## Автоматичні докази

```bash
cd backend && npm run build
cd backend && php artisan test
```

- Збірка Vite без помилок; PHPUnit — у т.ч. `ChatApiTest::test_messages_index_includes_avatar_url_when_set`, `data.avatar` у відповіді POST.

## API / контракт

- `ChatMessageResource` та broadcast `MessagePosted` містять поле `avatar` (nullable).
- `UserResource` містить `avatar_url: null` до реалізації T18.
- Оновлено `docs/chat-v2/openapi.yaml` (`ChatMessage.avatar`, `User.avatar_url`).

## Ручний чекліст (браузер)

1. **`/chat`** — у рядку повідомлення кольоровий квадрат з ініціалами (поки `avatar` з БД порожній); після T18 з URL — зображення.
2. **Сайдбар** — біля ніка «ви», друзів, привату, ігнору — ті самі аватарки/ініціали.
3. **Приватна панель** — аватар біля заголовка та в стрічці повідомлень (для «Ви» — ініціали поточного ніка).
4. **`/archive`** — колонка «Користувач»: аватар + нік.
