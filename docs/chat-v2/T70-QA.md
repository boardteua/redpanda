# T70 — QA evidence (slash: /topic, /clear кімнати)

## Scope

- Реєстр: `topic`, `clear` у `AppServiceProvider` → `SlashCommandRegistry`.
- Права: `RoomPolicy::updateDetails` (творець кімнати, `can:moderate`, або кімната без творця — лише staff).
- `/topic`: після успішного automod — оновлення `rooms.topic`, `broadcast(RoomTopicUpdated)`, публічне повідомлення в стрічці.
- `/clear`: масовий soft-delete `type = public` у поточній кімнаті, `RoomJournalCleared` на `room.{id}`, відповідь `client_only`.
- Видимість: `ChatMessage::scopeVisibleInRoomForUser` + архів — `whereNull('post_deleted_at')` для публічних.
- Vue: `RoomTopicUpdated`, `RoomJournalCleared`, після успішного POST з `meta.slash.name === 'clear'` — `applyRoomJournalCleared`.

## Автоматичні перевірки

```bash
cd backend && php artisan test --filter=ChatApiTest
cd backend && php artisan test --filter=ChatArchiveApiTest
cd backend && npm run build
```

У фіче-тесті `test_slash_clear_mod_soft_deletes_public_messages_only` для `GET` після `POST` під іншим користувачем використано `Sanctum::actingAs($alice)`, щоб уникнути залипання сесії `web` guard після `actingAs($mod, 'web')`.

Очікування: усі тести з фільтром `ChatApiTest` — PASS; збірка фронту — без помилок.

## Ручний сценарій (опційно)

1. Модератор у кімнаті: `/topic Тестова тема` — заголовок/опис кімнати в UI оновлюється; інший клієнт у тій самій кімнаті бачить нову тему (Echo).
2. Той самий користувач: `/clear` — публічні рядки зникають зі стрічки; інлайн-приват лишається.

## Вердикт

PASS після успішного виконання команд вище.
