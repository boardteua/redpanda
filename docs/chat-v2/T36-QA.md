# T36 — Редагування повідомлень у стрічці (QA)

**Задача:** PATCH повідомлення з політикою RBAC, `post_edited_at`, broadcast `MessageUpdated`, олівець у стрічці, режим редагування в композері, стрілки ↑/↓ на першому/останньому рядку textarea для циклу доступних дописів.

## Матриця сценаріїв (бекенд — PHPUnit)

| Роль / умова | Очікування | Тест |
|--------------|------------|------|
| Власник, публічне повідомлення, у межах вікна N год | 200, текст оновлено, `MessageUpdated` | `patch message owner updates text and broadcasts` |
| Гість | 403 | `patch message guest forbidden` |
| Чужий допис (звичайний юзер) | 403 | `patch message cannot edit other user` |
| Звичайний юзер, вік > N год | 403 | `patch message plain user forbidden after edit window` |
| VIP, власний допис, після вікна | 200 | `patch message vip can edit after edit window` |
| Модератор, чужий допис (не адмін) | 200 | `patch message moderator edits user message` |
| Модератор, автор — адмін | 403 | `patch message moderator cannot edit admin authored message` |
| Адмін, допис адміна | 200 | `patch message admin edits admin authored message` |
| Невірна кімната в URL | 404 | `patch message wrong room returns 404` |
| Inline private | 403 | `patch inline private message forbidden` |

Конфіг **N** годин: `config/chat.php` → `message_edit_window_hours`, env `CHAT_MESSAGE_EDIT_WINDOW_HOURS` (дефолт 24). У відповіді `/api/v1/me` для зареєстрованого — поле `message_edit_window_hours` (див. `MeProfileApiTest`).

## Автоматичні докази

```bash
cd backend && npm run build
cd backend && php artisan test
```

- **Вердикт:** PASS (на момент закриття T36).
- Vite: збірка без помилок; PHPUnit: **113 passed** (у т.ч. сценарії PATCH вище).

## Ручний чекліст (браузер)

1. **Олівець** — видно лише для повідомлень, де API повернув `can_edit=true`; `aria-label` на кнопці; після збереження текст оновлено, за наявності `post_edited_at` — мітка «змінено».
2. **Режим редагування** — банер «Редагування…» / скасувати; надсилання виконує PATCH замість POST; paste зображення та тулбар «мої зображення» вимкнені в режимі edit.
3. **Стрілки** — на **першому** рядку поля ↑ переходить до попереднього доступного для редагування допису; на **останньому** рядку ↓ — до наступного; без конфлікту з багаторядковим текстом посередині.
4. **Інший клієнт / вкладка** — після PATCH стрічка оновлюється через Echo (подія `MessageUpdated`).

## Скріншоти

За потреби до релізу: рядок з олівцем + мітка «змінено»; композер у режимі редагування з банером.
