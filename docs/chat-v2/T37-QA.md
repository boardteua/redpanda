# T37 — Видалення повідомлень у стрічці (QA)

**Задача:** DELETE з тією ж RBAC, що й T36; soft-delete (`post_deleted_at`, очищення тіла та вкладення); ідемпотентний повторний DELETE без другого broadcast; подія `MessageDeleted`; OpenAPI; UI — кошик, модал підтвердження (a11y), оновлення стрічки + Echo.

## Матриця сценаріїв (бекенд — PHPUnit)

| Роль / умова | Очікування | Тест |
|--------------|------------|------|
| Власник, публічне | 200, `post_message` порожнє, `can_edit`/`can_delete` false, `MessageDeleted` | `delete message owner soft deletes and broadcasts` |
| Повторний DELETE власника | 200, без нового broadcast | `delete message second time idempotent no broadcast` |
| Гість | 403 | `delete message guest forbidden` |
| Чужий допис | 403 | `delete message cannot delete other user` |
| Звичайний юзер, вік > N год | 403 | `delete message plain user forbidden after edit window` |
| VIP, після вікна | 200 | `delete message vip can delete after edit window` |
| Модератор, чужий (не адмін) | 200 | `delete message moderator deletes user message` |
| Модератор, автор — адмін | 403 | `delete message moderator cannot delete admin authored message` |
| Адмін, допис адміна | 200 | `delete message admin deletes admin authored message` |
| Невірна кімната | 404 | `delete message wrong room returns 404` |
| Inline private | 403 | `delete inline private message forbidden` |
| PATCH після видалення | 403 | `patch deleted message forbidden` |

**Інлайн-приват (T25):** видалення через цей endpoint не підтримується (дзеркало заборони PATCH).

## Автоматичні докази

```bash
cd backend && npm run build
cd backend && php artisan test
```

- **Вердикт:** PASS (на момент закриття T37).
- PHPUnit: **125 passed**; Vite build: OK.

## Ручний чекліст (браузер)

1. **Кошик** — лише коли `can_delete`; фокус і `aria-label`; після підтвердження — рядок «Повідомлення видалено», без картинки та без кліків згадати/приват по аватарці для видаленого рядка.
2. **Модал** — Escape і «Скасувати» закривають без запиту; Tab цикл усередині; фокус повертається на тригер.
3. **Редагування** — якщо відкрито редагування того ж `post_id`, після успішного DELETE композер скидає режим edit.
4. **Другий клієнт** — після DELETE інший підписник бачить оновлення через `MessageDeleted`.

## Скріншоти

За потреби оператора: модал підтвердження; рядок-плейсхолдер після видалення.
