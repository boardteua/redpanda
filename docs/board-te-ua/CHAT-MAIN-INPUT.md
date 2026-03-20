# Основний чат: стрічка, прокрутка, ввід, смайли, вкладення

**Сайт:** [board.te.ua](https://www.board.te.ua/)  
**Оновлено:** 2026-03-20  
**Метод:** Chrome DevTools MCP — скрол DOM, кліки, POST у мережі, тестове повідомлення в загальний чат.

---

## Прокрутка стрічки повідомлень

- Повідомлення рендеряться у **`ul`** всередині контейнера **`#show_chat`** (клас `background_box`).
- Саме цей **`ul`** має `overflow-y: auto` і великий `scrollHeight` (тисячі пікселів) при обмеженій висоті вікна перегляду (~960px) — **основна прокрутка чату**.
- Для навігації зручно виставляти `scrollTop`: `0` — до старих повідомлень, `scrollHeight - clientHeight` — до найновіших.

**Скріншоти:** [chat-scroll-top.png](screenshots/chat-scroll-top.png), [chat-scroll-middle.png](screenshots/chat-scroll-middle.png), [chat-scroll-bottom.png](screenshots/chat-scroll-bottom.png).

---

## Панель над полем вводу (`#main_emoticon` + `#container_input`)

У верхній частині блоку вводу:

- **Колір фону повідомлення** — `#high_pick` (іконка «заливка»).
- **Колір тексту** — `#text_pick`.
- **Напівжирний / курсив / підкреслення** — `#bold_item`, `#italic_item`, `#underline_item` (перемикачі `value` 0/1).
- **Вихід** — `.logout_button`.
- Посилання-іконки: архів, мої картинки, чат-рулетка, відкриття **`#chat_panel`** (див. [CHAT-PANEL-SIDEBAR.md](CHAT-PANEL-SIDEBAR.md)).

---

## Форма повідомлення (`#main_input`, `name="chat_data"`)

| Елемент | Призначення |
|---------|-------------|
| `#main_chat_type` | Прихований тип чату (`value="1"` у тесті). |
| `#this_target` | Ціль (`none` — загальний чат). |
| `#user_name`, `#user_room` | Поточний користувач і кімната. |
| `#content` | **Основне поле** (`type="text"`, до 999 символів, placeholder про **⇧ Shift** для редагування останніх повідомлень). |
| `#content-edit` | Резервне поле (у DOM приховане `display:none`). |
| `#submit_button` | Кнопка надсилання (`<i class="fa fa-paper-plane">`). |

### Надсилання на сервер

Після сабміту форми йде **XHR POST** на `https://www.board.te.ua/system/chat_process.php` з тілом `application/x-www-form-urlencoded`, наприклад:

- `content` — текст повідомлення;
- `bold`, `italic`, `underline` — стан форматування;
- `high`, `color` — кольори з пікерів (можуть бути порожні);
- `target` — ціль (наприклад `none`).

Відповідь може бути порожньою при успішній відправці; оновлення стрічки підтягується наступними запитами до `system/chat_log.php`.

**Тест у чаті:** відправлено повідомлення з текстом на кшталт *«[doc] тест повідомлення з автодослідження»* та смайлом `:hi:` — воно з’явилось у стрічці від ніка тестового акаунта. Скрін: [chat-after-send.png](screenshots/chat-after-send.png).

---

## Смайли (`#emo_item`, `#emo_list`)

- **Кнопка** — комірка таблиці `#emo_item` з іконкою `fa-smile-o`.
- **Панель** — `#emo_list`: у відкритому стані блок ~235×240px над нижньою зоною екрана, всередині **сотні** елементів `.emoticon` з `img.chat_emoticon`.
- Кожен GIF підвантажується з `/emoticon/*.gif`; у `title` задається код на кшталт `:hi:`, у `onclick` викликається `emoticon(document.chat_data.content, ':код:')` — рядок додається в поле `#content`.
- Повторне натискання `#emo_item` згортає панель (висота `#emo_list` стає 0).

**Скрін:** [chat-emoji-panel-open.png](screenshots/chat-emoji-panel-open.png).

---

## Вкладення зображень

- Комірка **`#send_image`** (іконка `fa-file-image-o`) — зона вибору зображення поруч із полем тексту.
- У формі є **`<input type="file" id="file_image" name="file" class="upload" accept="image/*">`** у обгортці **`.fileUpload.sub_button`** (іконка `fa-folder-open-o`). Для стилізації поле часто **`opacity: 0`** і позиціонується поверх клікабельної зони — стандартний патерн «прихований file input».
- У цьому огляді **файл не вибирався** (діалог ОС недоступний для автоматизації); зафіксовано лише структуру DOM і обмеження `image/*`.

---

## Пов’язані документи

- Загальна будова сайту: [SITE-STRUCTURE.md](SITE-STRUCTURE.md)  
- Правий сайдбар: [CHAT-PANEL-SIDEBAR.md](CHAT-PANEL-SIDEBAR.md)  
- Приватні повідомлення: [PRIVATE-MESSAGES.md](PRIVATE-MESSAGES.md)
