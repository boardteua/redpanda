# Панель `#chat_panel` (правий сайдбар чату)

**Сайт:** [board.te.ua](https://www.board.te.ua/)  
**Оновлено:** 2026-03-20  
**Метод:** Chrome DevTools MCP — перегляд DOM, кліки по іконках і вкладках, скріншоти viewport.

Панель — це контейнер `div#chat_panel` з класами `panels panelone`. У відкритому стані має фіксовану ширину **320px**; позиція задається інлайново через `style.right` (**320px** — відкрито, **0px** — сховано в «згорнутий» стан).

---

## Відкриття та закриття

| Дія | Як працює |
|-----|------------|
| **Закрити** | Іконка хрестика у верхній частині панелі: `i.close_panel` (Font Awesome `fa-close`, атрибут `value="chat_panel"`). |
| **Відкрити знову** | У основній смузі чату є окрема іконка групи людей: `i.fa-users.icon_bar` (без `id`) — повертає панель у відкритий стан (`right: 320px`). |

---

## Верхній ряд іконок (вкладки панелі)

Блок: `.inner_top_option` всередині `.top_option`. Кожна іконка — це `<i>` з `title` (підказка) та власним `id`:

| ID | Підказка (`title`) | FA-класи (орієнтир) | Вміст основної зони (`.panel_element`) |
|----|---------------------|---------------------|----------------------------------------|
| `chat_user` | Люди | `fa-users` | `#container_user` — список онлайн-користувачів |
| `chat_friends` | Друзі | `fa-address-book` | `#menu_friend` — підвкладки друзів |
| `chat_private` | Приват | `fa-comments` (+ клас `icon_new_private`) | Текст-заглушка або список ПП; у порожньому стані: *«Немає нових повідомлень»* |
| `chat_room` | Кімнати | `fa-home` | `#container_user_room` — список кімнат |
| `chat_ignore` | Ігнор | `fa-user-times` | Текст; у порожньому стані: *«Список ігнор порожній»* |

Перемикання вкладки **замінює** вміст `.panel_element` відповідним блоком.

---

## Вкладка «Люди» (`#chat_user`)

- Список: `ul` у `#container_user`, елементи `li.users_option` з класом рангу (`rank1`, `rank5`, …).
- Кожен рядок: аватар `.avatar_userlist`, нік у `p.usertarget` (для себе — `id` збігається з логіном).
- **Клік по рядку** (`.open_user`) відкриває/закриває контекстне меню `.option_list` → `ul.user_option_list`.

### Меню для **власного** ніка

| Пункт | `value` атрибута `li` | Результат |
|-------|------------------------|-----------|
| Інформація | `get_info` | Має відкривати панель профілю користувача (`#users_options`; у тестовому проходженні вміст міг ще не підвантажитись). |
| Команди | `help_panel` | Відкривається **окрема верхня панель** `#help_panel` (не всередині `chat_panel`) з довідкою по slash-командах (`/away`, `/me`, `/seen`, `/msg`, `/friend`, приватні `/clear`, `/ignore`, …). |
| Профіль | `tools_panel` | Відкривається **окрема верхня панель** `#tools_panel` з вкладками налаштувань (див. нижче). |

### Меню для **іншого** користувача (приклад: `org100h`)

| Пункт | `value` / клас | Результат |
|-------|-----------------|-----------|
| Інформація | `get_info` | Як вище. |
| Приватний чат | нік у `value` (клас `send_private`) | Відкриває **`#private_panel`**, підвантажує історію через `private_log2.php`, надсилання — **`POST private_process.php`** (детально та з тестом на **org100h**: [PRIVATE-MESSAGES.md](PRIVATE-MESSAGES.md)). |
| Додати до списку друзів | `get_friends` | Запит/дія дружби. |

---

## Вкладка «Друзі» (`#chat_friends`)

Контейнер `#menu_friend` містить **дві суб-кнопки** (половинки рядка):

| Кнопка | `value` | Вміст |
|--------|---------|--------|
| **Активний** | `active_friend` | Список поточних друзів (у тесті — порожньо, якщо друзів немає). |
| **Запити на дружбу** | `pending_friend` | Текст *«Немає запитів у друзі»*, якщо запитів немає. |

Активна суб-вкладка має клас `selected_element` на `button.friend_button`.

---

## Вкладка «Приват» (`#chat_private`)

- Якщо немає непрочитаних/активних потоків — показується центрований рядок: **«Немає нових повідомлень»** (`p.centered_element`).
- Окремий draggable-контейнер переписки: **`#private_panel`** — відкривається з меню користувача (**Приватний чат**) або з інших дій UI; повний опис приватів: [PRIVATE-MESSAGES.md](PRIVATE-MESSAGES.md).

---

## Вкладка «Кімнати» (`#chat_room`)

Блок `#container_user_room` містить картки кімнат (`div` з `id` на кшталт `room1`, `room3`, …). У тесті відображались, зокрема:

- **Main** — загальна кімната з лічильником учасників;
- **Закрита група**, **Vip**, **Львівський чат**, **Стіна плачу** — кожна з підписом типу «Загальна *N*» або власним статусом.

Клік по кімнаті, ймовірно, перемикає активний канал (потребує окремої перевірки в мережевих запитах).

---

## Вкладка «Ігнор» (`#chat_ignore`)

- У порожньому списку: **«Список ігнор порожній»**.

---

## Панель «Профіль» (`#tools_panel`) — з меню «Профіль» власного ніка

Це **не** частина `#chat_panel`, а окрема панель класу `top_panels panelone`. Закривається тим самим патерном: `.close_panel` / `top_icon_close`.

### Вкладки-кнопки всередині `tools_panel`

1. **Персональна інформація** — зміна аватара: **Вибрати файл**, **Оновити**, вибір **Країни** (довгий `<select>`), кнопка **Оновлення інформації**.
2. **Інформація про акаунт** — **Змінити e-mail**, поля пароля (**Старий / Новий / Підтвердіть**), **Змінити пароль**.
3. **соц.мережі** — посилання/поля для **Facebook, Twitter, Pinterest, Google+, youtube, Instagram, Linked in, Tumblr, flikr** та кнопка **Оновлення інформації**.

---

## Панель «Команди» (`#help_panel`)

Відкривається з пункту **Команди** в меню власного користувача. Містить заголовки та описи команд (статус `/away`, `/me`, `/seen`, `/msg`, `/friend`, приватні `/clear`, `/ignore`, `/ignoreclear`) — той самий текст, що й у загальній довідці в інтерфейсі.

---

## Скріншоти (viewport)

| Файл | Опис |
|------|------|
| [screenshots/sidebar-01-users.png](screenshots/sidebar-01-users.png) | Вкладка «Люди», список онлайн. |
| [screenshots/sidebar-02-friends.png](screenshots/sidebar-02-friends.png) | «Друзі» → підвкладка «Активний». |
| [screenshots/sidebar-03-private.png](screenshots/sidebar-03-private.png) | «Приват», порожній стан. |
| [screenshots/sidebar-04-rooms.png](screenshots/sidebar-04-rooms.png) | «Кімнати». |
| [screenshots/sidebar-05-ignore.png](screenshots/sidebar-05-ignore.png) | «Ігнор», порожній список. |
| [screenshots/sidebar-06-panel-closed.png](screenshots/sidebar-06-panel-closed.png) | Після закриття хрестиком (`right: 0px`). |
| [screenshots/sidebar-07-reopened.png](screenshots/sidebar-07-reopened.png) | Панель знову відкрита через `i.fa-users.icon_bar`. |
| [screenshots/sidebar-08-help-panel.png](screenshots/sidebar-08-help-panel.png) | Панель `#help_panel` (команди). |
| [screenshots/sidebar-09-tools-personal.png](screenshots/sidebar-09-tools-personal.png) | `#tools_panel` → Персональна інформація. |
| [screenshots/sidebar-10-tools-account.png](screenshots/sidebar-10-tools-account.png) | `#tools_panel` → Інформація про акаунт. |
| [screenshots/sidebar-11-tools-social.png](screenshots/sidebar-11-tools-social.png) | `#tools_panel` → соц.мережі. |
| [screenshots/sidebar-12-friends-pending.png](screenshots/sidebar-12-friends-pending.png) | «Друзі» → «Запити на дружбу». |

---

## Інші панелі в розмітці (орієнтир для розробки)

За наявністю в DOM: `#main_option`, `#users_options`, `#tools_panel`, `#help_panel`, `#profile_panel`, `#addon_panel`, `#addon_panel_full`, `#private_panel` — різні шари UI (верхні панелі, профіль, аддони, приват). Детальний опис виходить за межі лише `chat_panel`, але **Команди** та **Профіль** логічно прив’язані до меню користувача в списку «Люди».

---

*Документ доповнює [SITE-STRUCTURE.md](SITE-STRUCTURE.md).*
