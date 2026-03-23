# T84 — інвентаризація великих Vue SFC (декомпозиція)

Мета: зменшити розмір і зв’язність однофайлових компонентів без зміни API, поведінки чату/staff і без міграції на Vue 3.

## Пріоритет за розміром (рядки, орієнтовно)

| Файл | Рядки | Відповідальність | Статус |
|------|-------|------------------|--------|
| `views/ChatRoom.vue` | ~2690 | Оркестрація кімнати, завантаження повідомлень, WS, сайдбар, модалки | **Кандидат на наступні ітерації** — розбити на контейнер + підкомпоненти модалок/стану |
| `views/StaffUsersView.vue` | ~1125 | Таблиця користувачів, фільтри, дії staff | Кандидат: рядок таблиці, панель фільтрів |
| `components/chat/sidebar/ChatRoomSidebar.vue` | ~1104 | Вкладки сайдбару, списки peers, приват | Кандидат: окремі tab-панелі, блок «користувачі в кімнаті» |
| `views/AuthWelcome.vue` | ~736 | Лендінг / вітання після auth | Кандидат: секції за змістом |
| `components/chat/composer/ChatRoomComposer.vue` | композер | Поле вводу, медіа | Підкомпоненти в `composer/` |
| `views/ArchiveChat.vue` | ~391 | Архів стрічки | Нижчий пріоритет |
| `views/StaffStopWordsView.vue` | ~441 | Stop words | Нижчий пріоритет |
| `components/chat/feed/ChatFeedMessageRow.vue` | ~215 | Рядок повідомлення | Вже помірний; за потреби — меню дій / тіло |

## Зміни в цьому циклі (T84)

- `composer/ChatRoomComposerToolbar.vue` — панель форматування, палітри bg/fg, кнопки «мої зображення», архів, вихід.
- `composer/ChatRoomComposerEditBanner.vue` — рядок «Редагування повідомлення» + скасувати.
- `composer/ChatRoomComposerAttachmentPreviews.vue` — прев’ю існуючого вкладення при edit, прев’ю нового зображення, текст помилки завантаження.

Публічний API `ChatRoomComposer` (props, події, методи через `ref`) **не змінювався**.

## Каталоги під `components/chat/`

| Каталог | Зміст |
|---------|--------|
| `feed/` | Стрічка: `ChatFeedMessageList`, `ChatFeedMessageRow`, `ChatMessageBody`, `ChatOembedBlock` |
| `composer/` | Композер і пов’язані модалки смайлів/бібліотеки зображень |
| `sidebar/` | `ChatRoomSidebar`, вкладки, бейджі peer |
| `room/` | Колонка кімнати (`ChatRoomMainColumn`, `ChatRoomHeader`), агрегатор модалок, `AddRoomModal`, `RoomEditModal` |

## Продовження (контейнери → вкладки → модалки)

- `room/ChatRoomMainColumn.vue` — колонка стрічки: шапка, банери стану, `<main>` + слот для списку повідомлень і композера. У `ChatRoom.vue` лишаються затемнення та обгортка `rp-chat-external-wrap` разом із сайдбаром (flex-паритет як раніше).
- `sidebar/ChatSidebarTabBars.vue` — мобільний і десктопний рядки вкладок сайдбару; refs `panelCloseBtnMobile` / `panelCloseBtnDesktop` для фокусу з `ChatRoom` через `chatRoomSidebar.$refs.sidebarTabBars`.
- `room/ChatRoomModals.vue` — усі модалки екрану чату (довідка, профіль, налаштування, підтвердження, кімнати); події проброшені на батьківський `ChatRoom`.

## Наступні кроки (поза цим PR, за потреби)

1. `ChatRoom.vue`: винести групи модалок і логіку «стан кімнати» у підкомпоненти або composables utils (лише якщо зберігається Options API і ті самі dispatch/commit).
2. `sidebar/ChatRoomSidebar.vue`: винести окремі tab-панелі («Люди», «Друзі», …) у підкомпоненти.
3. `StaffUsersView.vue`: `StaffUsersTableRow.vue`, `StaffUsersFiltersBar.vue`.
