# T71 — QA evidence (slash: адміністратор)

## Scope

- Права: `AdminSlashCommandHelper::requireChatAdmin` → лише `user_rank >= RANK_ADMIN`, не гість.
- Ролі: `/setuser`, `/setmod`, `/setvip`, `/setadmin` — `StaffRoleSlashCommandHandler` + ті самі обмеження, що `StaffUserController::update` (через `canReceiveStaffManagementFrom`, без дій проти себе).
- Невидимість: `/invisible`, `/visible` — `users.presence_invisible`; payload Echo `presence_invisible`; фільтр у `ChatRoom.vue` для списку «Онлайн»; після slash — `reconnect_echo` (перепідключення Echo).
- `/silent On|Off` — `chat_settings.silent_mode`; WS `ChatSilentModeUpdated` на всі кімнати; GET/PATCH налаштувань + чекбокс у `ChatSettingsModal`.
- `/gsound` — подія `GlobalSoundPlayed`; клієнт: `maybePlayGlobalGsound` (`/sounds/whistle.mp3`), з урахуванням `silent_mode` та «не від себе».
- `/global` — автомод, rate limit (`slash-global:{user_id}` 5/год), по одному публічному рядку на кімнату з `post_style.global`; у поточній кімнаті `client_message_id` = з POST (idempotency); відповідь HTTP — рядок поточної кімнати без повторного broadcast у контролері (`SlashCommandOutcome::persistedPublicMessage`).

## Автоматичні перевірки

```bash
cd backend && php artisan test --filter=AdminSlashCommandsApiTest
cd backend && php artisan test --filter=ChatApiTest
cd backend && npm run build
```

Очікування: усі тести з фільтрами вище — PASS; збірка фронту — без помилок.

## Ручний сценарій (опційно)

1. Два акаунти: адмін і звичайний користувач. Адмін: `/setmod нік` — у жертви оновлюється роль; мод без `chat-admin` отримує 403 на ту ж команду.
2. `/silent on` — у другого клієнта після події (або перезавантаження) немає newpost/pmsound; `/silent off` — знову є (за префами користувача).
3. `/invisible` — після перепідключення Echo інший клієнт не бачить адміна в «Онлайн».
4. `/global Текст` — рядок з’являється в усіх кімнатах; повтор з тим самим `client_message_id` — дедуплікація в поточній кімнаті.

## Вердикт

PASS після успішного виконання команд вище.
